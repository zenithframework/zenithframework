<?php

declare(strict_types=1);

namespace Zenith\Security\Encryption;

class Hasher
{
    protected string $algo = 'sha256';
    protected bool $binary = false;

    public function hash(string $data, ?string $salt = null): string
    {
        $salt = $salt ?? random_bytes(16);
        return $salt . hash_hmac($this->algo, $data, $salt, $this->binary);
    }

    public function verify(string $data, string $hash): bool
    {
        $salt = substr($hash, 0, 16);
        $expected = $this->hash($data, $salt);
        return hash_equals($expected, $hash);
    }

    public function setAlgo(string $algo): void
    {
        $this->algo = $algo;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }
}

class Encrypter
{
    protected string $key;
    protected string $cipher = 'aes-256-gcm';
    protected int $ivLength = 16;

    public function __construct(?string $key = null)
    {
        $rawKey = $key ?? config('app.key') ?? null;

        if ($rawKey === null || $rawKey === '') {
            throw new \RuntimeException(
                'Encryption key is not configured. Set APP_KEY in your .env file before using the Encrypter.'
            );
        }

        // Support Laravel-style "base64:" prefixed keys.
        if (str_starts_with($rawKey, 'base64:')) {
            $decoded = base64_decode(substr($rawKey, 7), true);
            if ($decoded !== false) {
                $rawKey = $decoded;
            }
        }

        if (strlen($rawKey) < 32) {
            throw new \RuntimeException('Encryption key must be at least 32 bytes (256 bits).');
        }

        // Derive a fixed-length 256-bit key. Hash the key material rather than
        // padding/truncating, which would weaken the key (e.g. "abcabcabc...").
        $this->key = hash('sha256', $rawKey, true);
    }

    public function encrypt(string $data, ?string $iv = null): string
    {
        $iv = $iv ?? random_bytes($this->ivLength);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $data): string
    {
        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        $tagLength = 16;
        if (strlen($decoded) < $this->ivLength + $tagLength) {
            throw new \RuntimeException('Invalid encrypted data');
        }

        $iv = substr($decoded, 0, $this->ivLength);
        $tag = substr($decoded, $this->ivLength, $tagLength);
        $ciphertext = substr($decoded, $this->ivLength + $tagLength);

        // Fail closed: GCM authentication tag is verified here. If the tag is
        // missing or wrong, openssl_decrypt returns false and we must not
        // return unauthenticated plaintext.
        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed (authentication tag mismatch)');
        }

        return $decrypted;
    }

    public function encryptArray(array $data): string
    {
        return $this->encrypt(json_encode($data));
    }

    public function decryptArray(string $data): array
    {
        return json_decode($this->decrypt($data), true);
    }
}

class TokenGenerator
{
    protected int $length = 32;
    protected string $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public function generate(?int $length = null): string
    {
        $length = $length ?? $this->length;
        $token = '';
        $max = strlen($this->charset) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $token .= $this->charset[random_int(0, $max)];
        }
        
        return $token;
    }

    public function generateSecure(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public function generateNumeric(int $length = 6): string
    {
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= (string) random_int(0, 9);
        }
        return $token;
    }

    public static function bearer(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        
        return null;
    }

    public static function basic(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (str_starts_with($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6));
            if ($decoded !== false) {
                $parts = explode(':', $decoded, 2);
                if (count($parts) === 2) {
                    return ['username' => $parts[0], 'password' => $parts[1]];
                }
            }
        }
        
        return null;
    }
}

class CSRFToken
{
    protected string $sessionKey = '_token';
    protected int $tokenLength = 32;

    public function generate(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    public function get(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = $this->generate();
        }
        
        return $_SESSION[$this->sessionKey];
    }

    public function field(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->get() . '">';
    }

    public function token(): string
    {
        return $this->get();
    }

    public function verify(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $sessionToken = $_SESSION[$this->sessionKey] ?? '';
        
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    public function validate(): bool
    {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if ($token === null) {
            return false;
        }
        
        return $this->verify($token);
    }

    public function refresh(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION[$this->sessionKey] = $this->generate();
        
        return $_SESSION[$this->sessionKey];
    }
}