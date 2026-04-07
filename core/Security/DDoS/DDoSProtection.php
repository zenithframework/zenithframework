<?php

declare(strict_types=1);

namespace Zen\Security\DDoS;

class TrafficAnalyzer
{
    protected array $requestCounts = [];
    protected array $ipScores = [];
    protected int $windowSize = 60;
    protected int $maxRequestsPerWindow = 1000;
    protected array $suspiciousPatterns = [];

    public function analyze(string $ip, array $request): array
    {
        $this->recordRequest($ip);
        
        $score = $this->calculateThreatScore($ip, $request);
        
        return [
            'ip' => $ip,
            'score' => $score,
            'level' => $this->getThreatLevel($score),
            'should_block' => $score > 80,
            'should_challenge' => $score > 40 && $score <= 80,
        ];
    }

    public function recordRequest(string $ip): void
    {
        $now = time();
        
        if (!isset($this->requestCounts[$ip])) {
            $this->requestCounts[$ip] = [];
        }
        
        $this->requestCounts[$ip][] = $now;
        
        $this->requestCounts[$ip] = array_filter(
            $this->requestCounts[$ip],
            fn($time) => ($now - $time) < $this->windowSize
        );
    }

    public function getRequestCount(string $ip): int
    {
        return count($this->requestCounts[$ip] ?? []);
    }

    public function calculateThreatScore(string $ip, array $request): int
    {
        $score = 0;
        
        $requestCount = $this->getRequestCount($ip);
        if ($requestCount > $this->maxRequestsPerWindow) {
            $score += 50;
        } elseif ($requestCount > $this->maxRequestsPerWindow * 0.5) {
            $score += 20;
        }
        
        $uri = $request['uri'] ?? '';
        if ($this->isSuspiciousUri($uri)) {
            $score += 30;
        }
        
        $userAgent = $request['user_agent'] ?? '';
        if ($this->isBotUserAgent($userAgent)) {
            $score += 25;
        }
        
        if ($this->isKnownBadIp($ip)) {
            $score += 40;
        }
        
        if ($this->hasInvalidHeaders($request)) {
            $score += 15;
        }
        
        if ($this->isPatternAnomaly($ip)) {
            $score += 20;
        }
        
        return min(100, $score);
    }

    protected function isSuspiciousUri(string $uri): bool
    {
        $suspicious = [
            '/wp-admin',
            '/wp-login',
            '/xmlrpc.php',
            '/admin',
            '/.env',
            '/config',
            '/phpinfo',
            '/shell',
            '/cmd',
            '/exec',
            '/etc/passwd',
            '/etc/shadow',
        ];
        
        foreach ($suspicious as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    protected function isBotUserAgent(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }
        
        $bots = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'ruby', 'go', 'httpclient', 'okhttp',
        ];
        
        $userAgentLower = strtolower($userAgent);
        foreach ($bots as $bot) {
            if (str_contains($userAgentLower, $bot)) {
                return true;
            }
        }
        
        return false;
    }

    protected function isKnownBadIp(string $ip): bool
    {
        $file = dirname(__DIR__, 2) . '/storage/security/bad_ips.json';
        if (!file_exists($file)) {
            return false;
        }
        
        $badIps = json_decode(file_get_contents($file), true) ?? [];
        return in_array($ip, $badIps);
    }

    protected function hasInvalidHeaders(array $request): bool
    {
        $headers = $request['headers'] ?? [];
        
        if (empty($headers['User-Agent']) && empty($headers['user_agent'])) {
            return true;
        }
        
        if (isset($headers['X-Forwarded-For']) && count(explode(',', $headers['X-Forwarded-For'])) > 5) {
            return true;
        }
        
        return false;
    }

    protected function isPatternAnomaly(string $ip): bool
    {
        if (!isset($this->ipScores[$ip])) {
            $this->ipScores[$ip] = [];
        }
        
        $recentScores = array_slice($this->ipScores[$ip], -10);
        
        if (count($recentScores) > 5) {
            $avg = array_sum($recentScores) / count($recentScores);
            if ($avg > 50) {
                return true;
            }
        }
        
        $this->ipScores[$ip][] = rand(0, 10);
        
        return false;
    }

    protected function getThreatLevel(int $score): string
    {
        if ($score > 80) {
            return 'critical';
        } elseif ($score > 60) {
            return 'high';
        } elseif ($score > 40) {
            return 'medium';
        } elseif ($score > 20) {
            return 'low';
        }
        
        return 'normal';
    }

    public function setMaxRequests(int $max): void
    {
        $this->maxRequestsPerWindow = $max;
    }

    public function setWindowSize(int $seconds): void
    {
        $this->windowSize = $seconds;
    }

    public function getStats(): array
    {
        $totalIps = count($this->requestCounts);
        $totalRequests = array_sum(array_map('count', $this->requestCounts));
        
        $highThreatIps = 0;
        foreach ($this->requestCounts as $ip => $requests) {
            if (count($requests) > $this->maxRequestsPerWindow * 0.8) {
                $highThreatIps++;
            }
        }
        
        return [
            'total_ips' => $totalIps,
            'total_requests' => $totalRequests,
            'high_threat_ips' => $highThreatIps,
            'max_requests_allowed' => $this->maxRequestsPerWindow,
            'window_size' => $this->windowSize,
        ];
    }

    public function reset(): void
    {
        $this->requestCounts = [];
        $this->ipScores = [];
    }
}

class DDoSProtection
{
    protected TrafficAnalyzer $analyzer;
    protected \Zen\Security\Firewall\IPBlocker $blocker;
    protected bool $enabled;
    protected int $challengeThreshold = 60;
    protected int $blockThreshold = 80;

    public function __construct()
    {
        $this->analyzer = new TrafficAnalyzer();
        $this->blocker = new \Zen\Security\Firewall\IPBlocker();
        $this->enabled = config('security.ddos_protection')['enabled'] ?? true;
    }

    public function check(string $ip, array $request): array
    {
        if (!$this->enabled) {
            return ['allowed' => true, 'action' => 'none'];
        }
        
        if ($this->blocker->isBlocked($ip)) {
            return [
                'allowed' => false,
                'action' => 'block',
                'reason' => 'IP is blocked',
            ];
        }
        
        $analysis = $this->analyzer->analyze($ip, $request);
        
        if ($analysis['should_block']) {
            $this->blocker->recordViolation($ip, 'ddos');
            $this->blocker->block($ip, 3600, 'DDoS protection: High threat score');
            
            return [
                'allowed' => false,
                'action' => 'block',
                'reason' => 'DDoS threat detected',
                'score' => $analysis['score'],
            ];
        }
        
        if ($analysis['should_challenge']) {
            return [
                'allowed' => true,
                'action' => 'challenge',
                'reason' => 'Suspicious traffic',
                'score' => $analysis['score'],
            ];
        }
        
        return [
            'allowed' => true,
            'action' => 'none',
            'score' => $analysis['score'],
        ];
    }

    public function generateChallenge(): string
    {
        $challenge = bin2hex(random_bytes(16));
        
        $_SESSION['ddos_challenge'] = $challenge;
        $_SESSION['ddos_challenge_time'] = time();
        
        return $challenge;
    }

    public function verifyChallenge(string $answer): bool
    {
        $challenge = $_SESSION['ddos_challenge'] ?? '';
        $challengeTime = $_SESSION['ddos_challenge_time'] ?? 0;
        
        if (empty($challenge) || (time() - $challengeTime) > 300) {
            return false;
        }
        
        $expected = $this->hashChallenge($challenge);
        
        unset($_SESSION['ddos_challenge']);
        unset($_SESSION['ddos_challenge_time']);
        
        return hash_equals($expected, $answer);
    }

    protected function hashChallenge(string $challenge): string
    {
        return hash('sha256', $challenge . config('app.key') ?? 'zen');
    }

    public function getStats(): array
    {
        return $this->analyzer->getStats();
    }
}

class Challenge
{
    protected string $type;
    protected int $difficulty;

    public function __construct(string $type = 'js', int $difficulty = 1)
    {
        $this->type = $type;
        $this->difficulty = $difficulty;
    }

    public function generate(): array
    {
        return match ($this->type) {
            'js' => $this->generateJavaScriptChallenge(),
            'math' => $this->generateMathChallenge(),
            'captcha' => $this->generateCaptchaChallenge(),
            default => $this->generateJavaScriptChallenge(),
        };
    }

    protected function generateJavaScriptChallenge(): array
    {
        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        $html = <<<HTML
<script>
(function() {
    var token = "{$token}";
    var ts = {$timestamp};
    var hash = btoa(token + ts);
    document.cookie = "ddos_token=" + hash + "; path=/; SameSite=Strict";
    window.location.reload();
})();
</script>
HTML;

        return [
            'type' => 'javascript',
            'token' => $token,
            'html' => $html,
            'expires' => $timestamp + 300,
        ];
    }

    protected function generateMathChallenge(): array
    {
        $a = rand(1, 20);
        $b = rand(1, 20);
        $op = rand(0, 1) ? '+' : '-';
        $answer = $op === '+' ? $a + $b : $a - $b;
        
        $token = bin2hex(random_bytes(16));
        
        return [
            'type' => 'math',
            'token' => $token,
            'question' => "{$a} {$op} {$b} = ?",
            'answer_hash' => hash('sha256', (string)$answer),
            'expires' => time() + 300,
        ];
    }

    protected function generateCaptchaChallenge(): array
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return [
            'type' => 'captcha',
            'code' => $code,
            'image' => base64_encode($this->generateCaptchaImage($code)),
            'expires' => time() + 300,
        ];
    }

    protected function generateCaptchaImage(string $code): string
    {
        $width = 150;
        $height = 50;
        
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 240, 240, 240);
        $text = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $bg);
        
        $font = 5;
        $x = 10;
        for ($i = 0; $i < strlen($code); $i++) {
            imagestring($image, $font, $x, 15, $code[$i], $text);
            $x += 20;
        }
        
        for ($i = 0; $i < 5; $i++) {
            $lineColor = imagecolorallocate($image, rand(150, 200), rand(150, 200), rand(150, 200));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }
        
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        
        return $png;
    }
}