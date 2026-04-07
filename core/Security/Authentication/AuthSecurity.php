<?php

declare(strict_types=1);

namespace Zen\Security\Authentication;

class LoginThrottler
{
    protected array $attempts = [];
    protected int $maxAttempts = 5;
    protected int $lockoutDuration = 300;
    protected string $storagePath;

    public function __construct()
    {
        $this->storagePath = dirname(__DIR__, 2) . '/storage/security';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function recordAttempt(string $identifier, bool $success): void
    {
        $key = $this->getKey($identifier);
        
        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [
                'attempts' => 0,
                'locked' => false,
                'locked_at' => null,
            ];
        }

        if ($success) {
            unset($this->attempts[$key]);
            $this->persist();
            return;
        }

        $this->attempts[$key]['attempts']++;
        
        if ($this->attempts[$key]['attempts'] >= $this->maxAttempts) {
            $this->attempts[$key]['locked'] = true;
            $this->attempts[$key]['locked_at'] = time();
        }

        $this->persist();
    }

    public function isLocked(string $identifier): bool
    {
        $key = $this->getKey($identifier);
        
        if (!isset($this->attempts[$key]) || !$this->attempts[$key]['locked']) {
            return false;
        }

        $lockedAt = $this->attempts[$key]['locked_at'] ?? time();
        
        if ((time() - $lockedAt) > $this->lockoutDuration) {
            $this->unlock($identifier);
            return false;
        }

        return true;
    }

    public function getRemainingAttempts(string $identifier): int
    {
        $key = $this->getKey($identifier);
        $attempts = $this->attempts[$key]['attempts'] ?? 0;
        return max(0, $this->maxAttempts - $attempts);
    }

    public function getLockoutTimeRemaining(string $identifier): int
    {
        $key = $this->getKey($identifier);
        
        if (!isset($this->attempts[$key]['locked_at'])) {
            return 0;
        }

        $remaining = $this->lockoutDuration - (time() - $this->attempts[$key]['locked_at']);
        return max(0, $remaining);
    }

    public function unlock(string $identifier): void
    {
        $key = $this->getKey($identifier);
        unset($this->attempts[$key]);
        $this->persist();
    }

    public function clear(): void
    {
        $this->attempts = [];
        $this->persist();
    }

    protected function getKey(string $identifier): string
    {
        return hash('sha256', $identifier);
    }

    protected function persist(): void
    {
        $file = $this->storagePath . '/login_attempts.json';
        file_put_contents($file, json_encode($this->attempts));
    }

    protected function load(): void
    {
        $file = $this->storagePath . '/login_attempts.json';
        if (file_exists($file)) {
            $this->attempts = json_decode(file_get_contents($file), true) ?? [];
        }
    }

    public function setMaxAttempts(int $max): void
    {
        $this->maxAttempts = $max;
    }

    public function setLockoutDuration(int $seconds): void
    {
        $this->lockoutDuration = $seconds;
    }
}

class PasswordHasher
{
    protected string $algo = PASSWORD_BCRYPT;
    protected array $options = ['cost' => 12];

    public function hash(string $password): string
    {
        return password_hash($password, $this->algo, $this->options);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algo, $this->options);
    }

    public function check(string $password, string & $hash): bool
    {
        $valid = $this->verify($password, $hash);
        
        if ($valid && $this->needsRehash($hash)) {
            $hash = $this->hash($password);
        }
        
        return $valid;
    }

    public function setCost(int $cost): void
    {
        $this->options['cost'] = $cost;
    }

    public static function generate(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function quickHash(string $password): string
    {
        return hash('sha256', $password . config('app.key') ?? 'zen');
    }
}

class TwoFactor
{
    protected array $secrets = [];

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(16));
    }

    public function getQRCodeUrl(string $email, string $secret, string $issuer = 'Zen'): string
    {
        $issuer = rawurlencode($issuer);
        $email = rawurlencode($email);
        $secret = rawurlencode($secret);
        
        return "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}";
    }

    public function verify(string $secret, string $code): bool
    {
        $code = (int) $code;
        
        for ($i = -2; $i <= 2; $i++) {
            $expected = $this->getCode($secret, $i);
            if (hash_equals((string) $expected, (string) $code)) {
                return true;
            }
        }
        
        return false;
    }

    protected function getCode(string $secret, int $offset = 0): int
    {
        $secret = $this->base32Decode($secret);
        
        $time = floor(time() / 30) + $offset;
        $time = pack('N', $time);
        $time = str_pad($time, 8, "\0", STR_PAD_LEFT);
        
        $hash = hash_hmac('sha1', $time, $secret, true);
        
        $offset = ord(substr($hash, -1)) & 0x0F;
        
        $code = unpack('N', substr($hash, $offset, 4));
        $code = $code[1] & 0x7FFFFFFF;
        
        return $code % 1000000;
    }

    protected function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        
        for ($i = 0; $i < strlen($data); $i += 5) {
            $chunk = substr($data, $i, 5);
            $bits = '';
            
            for ($j = 0; $j < strlen($chunk); $j++) {
                $bits .= str_pad(decbin(ord($chunk[$j])), 8, '0', STR_PAD_LEFT);
            }
            
            while (strlen($bits) % 5 !== 0) {
                $bits .= '0';
            }
            
            for ($j = 0; $j < strlen($bits); $j += 5) {
                $output .= $alphabet[bindec(substr($bits, $j, 5))];
            }
        }
        
        return $output;
    }

    protected function base32Decode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        
        $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));
        
        for ($i = 0; $i < strlen($data); $i += 8) {
            $chunk = substr($data, $i, 8);
            $bits = '';
            
            for ($j = 0; $j < strlen($chunk); $j++) {
                $bits .= str_pad(decbin(strpos($alphabet, $chunk[$j])), 5, '0', STR_PAD_LEFT);
            }
            
            for ($j = 0; $j < strlen($bits); $j += 8) {
                $output .= chr(bindec(substr($bits, $j, 8)));
            }
        }
        
        return $output;
    }
}

class SessionGuard
{
    protected string $sessionName = 'zen_session';
    protected int $lifetime = 7200;
    protected bool $regenerate = true;
    protected array $config;

    public function __construct()
    {
        $this->config = config('security.session') ?? [];
        $this->sessionName = $this->config['name'] ?? 'zen_session';
        $this->lifetime = $this->config['lifetime'] ?? 7200;
        $this->regenerate = $this->config['regenerate'] ?? true;
    }

    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        $this->configure();
        
        return session_start();
    }

    protected function configure(): void
    {
        $cookieParams = session_get_cookie_params();
        
        session_set_cookie_params(
            $this->lifetime,
            $cookieParams['path'] ?? '/',
            $cookieParams['domain'] ?? '',
            $cookieParams['secure'] ?? false,
            $this->config['httponly'] ?? true
        );

        session_name($this->sessionName);

        if ($this->config['secure'] ?? false) {
            ini_set('session.cookie_secure', '1');
        }
        
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', $this->config['samesite'] ?? 'Strict');
    }

    public function login(array $user): void
    {
        $this->start();
        
        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($this->regenerate) {
            session_regenerate_id(true);
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
        }
    }

    public function user(): ?array
    {
        $this->start();
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        if ($this->config['verify_ip'] ?? true) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (($_SESSION['ip_address'] ?? '') !== $currentIp) {
                $this->logout();
                return null;
            }
        }

        if ($this->config['verify_user_agent'] ?? true) {
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (($_SESSION['user_agent'] ?? '') !== $currentAgent) {
                $this->logout();
                return null;
            }
        }

        if ($this->config['timeout'] ?? true) {
            $loginTime = $_SESSION['login_time'] ?? 0;
            if ((time() - $loginTime) > $this->lifetime) {
                $this->logout();
                return null;
            }
        }

        return $_SESSION['user'] ?? null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?string
    {
        return session_id() ?: null;
    }
}