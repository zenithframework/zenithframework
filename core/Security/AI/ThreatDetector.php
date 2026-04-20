<?php

declare(strict_types=1);

namespace Zenith\Security\AI;

class ThreatDetector
{
    protected array $threatScores = [];
    protected array $knownPatterns = [];
    protected int $learningWindow = 300;

    public function __construct()
    {
        $this->initPatterns();
    }

    protected function initPatterns(): void
    {
        $this->knownPatterns = [
            'sql_injection' => [
                'patterns' => ['UNION.*SELECT', 'DROP.*TABLE', 'INSERT.*INTO', '--', ';--'],
                'weight' => 80,
            ],
            'xss' => [
                'patterns' => ['<script', 'javascript:', 'onerror=', 'onload='],
                'weight' => 70,
            ],
            'path_traversal' => [
                'patterns' => ['\.\.\/', '\.\.\\', '%2e%2e'],
                'weight' => 60,
            ],
            'command_injection' => [
                'patterns' => [';.*\/bin\/sh', '\|.*cat', '\&\&.*rm', '\$\(', '`'],
                'weight' => 90,
            ],
            'scanning' => [
                'patterns' => ['admin', 'config', 'backup', 'phpinfo', '.env'],
                'weight' => 40,
            ],
        ];
    }

    public function analyze(array $request): array
    {
        $ip = $request['ip'] ?? 'unknown';
        $uri = $request['uri'] ?? '';
        $userAgent = $request['user_agent'] ?? '';
        $headers = $request['headers'] ?? [];
        $body = $request['body'] ?? '';

        $threatScore = 0;
        $detected = [];

        foreach ($this->knownPatterns as $name => $pattern) {
            $matches = $this->matchPatterns($uri . ' ' . $body, $pattern['patterns']);
            
            if ($matches > 0) {
                $threatScore += $pattern['weight'] * $matches;
                $detected[] = $name;
            }
        }

        if ($this->isSuspiciousUserAgent($userAgent)) {
            $threatScore += 30;
            $detected[] = 'suspicious_user_agent';
        }

        if ($this->isUnusualRequestPattern($ip, $request)) {
            $threatScore += 25;
            $detected[] = 'unusual_pattern';
        }

        if ($this->hasAnomalousHeaders($headers)) {
            $threatScore += 15;
            $detected[] = 'anomalous_headers';
        }

        $behaviorScore = $this->analyzeBehavior($ip);
        $threatScore += $behaviorScore;

        $this->recordThreat($ip, $threatScore);

        return [
            'ip' => $ip,
            'score' => min(100, $threatScore),
            'detected' => $detected,
            'level' => $this->getThreatLevel($threatScore),
            'action' => $this->determineAction($threatScore),
        ];
    }

    protected function matchPatterns(string $input, array $patterns): int
    {
        $count = 0;
        
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $input)) {
                $count++;
            }
        }
        
        return $count;
    }

    protected function isSuspiciousUserAgent(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return true;
        }

        $suspicious = ['curl', 'wget', 'python', 'scraper', 'bot', 'spider'];
        
        foreach ($suspicious as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function isUnusualRequestPattern(string $ip, array $request): bool
    {
        if (!isset($this->threatScores[$ip])) {
            $this->threatScores[$ip] = [];
        }

        $now = time();
        $recentRequests = array_filter(
            $this->threatScores[$ip],
            fn($ts) => ($now - $ts) < $this->learningWindow
        );

        $this->threatScores[$ip] = $recentRequests;
        $this->threatScores[$ip][] = $now;

        if (count($this->threatScores[$ip]) > 100) {
            return true;
        }

        $uri = $request['uri'] ?? '';
        
        if (count($recentRequests) > 50) {
            $uris = array_column($recentRequests, 'uri' ?? '');
            $uniqueUris = array_unique($uris);
            
            if (count($uris) > count($uniqueUris) * 2) {
                return true;
            }
        }

        return false;
    }

    protected function hasAnomalousHeaders(array $headers): bool
    {
        if (empty($headers['User-Agent']) && !empty($headers)) {
            return true;
        }

        if (isset($headers['X-Forwarded-For']) && str_contains($headers['X-Forwarded-For'], ',')) {
            return count(explode(',', $headers['X-Forwarded-For'])) > 3;
        }

        return false;
    }

    protected function analyzeBehavior(string $ip): int
    {
        if (!isset($this->threatScores[$ip])) {
            return 0;
        }

        $requests = $this->threatScores[$ip];
        
        if (count($requests) > 200) {
            return 30;
        }
        
        if (count($requests) > 100) {
            return 20;
        }

        return 0;
    }

    protected function recordThreat(string $ip, int $score): void
    {
        if (!isset($this->threatScores[$ip])) {
            $this->threatScores[$ip] = [];
        }

        $this->threatScores[$ip]['last_score'] = $score;
        $this->threatScores[$ip]['last_seen'] = time();
    }

    protected function getThreatLevel(int $score): string
    {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } elseif ($score >= 20) {
            return 'low';
        }
        return 'normal';
    }

    protected function determineAction(int $score): string
    {
        if ($score >= 80) {
            return 'block';
        } elseif ($score >= 60) {
            return 'challenge';
        } elseif ($score >= 40) {
            return 'throttle';
        }
        return 'allow';
    }

    public function getStats(): array
    {
        $totalIps = count($this->threatScores);
        $highThreat = 0;
        $mediumThreat = 0;

        foreach ($this->threatScores as $ip => $data) {
            $score = $data['last_score'] ?? 0;
            if ($score >= 60) {
                $highThreat++;
            } elseif ($score >= 40) {
                $mediumThreat++;
            }
        }

        return [
            'tracked_ips' => $totalIps,
            'high_threat' => $highThreat,
            'medium_threat' => $mediumThreat,
        ];
    }
}

class AnomalyDetector
{
    protected array $baseline = [];
    protected int $sensitivity = 2;

    public function __construct(int $sensitivity = 2)
    {
        $this->sensitivity = $sensitivity;
    }

    public function detect(array $metrics): array
    {
        $anomalies = [];

        foreach ($metrics as $name => $value) {
            if (!isset($this->baseline[$name])) {
                $this->baseline[$name] = [
                    'mean' => $value,
                    'std' => 0,
                    'count' => 1,
                ];
                continue;
            }

            $baseline = $this->baseline[$name];
            
            $newCount = $baseline['count'] + 1;
            $newMean = (($baseline['mean'] * $baseline['count']) + $value) / $newCount;
            $newStd = sqrt(
                (($baseline['std'] ** 2 * $baseline['count']) + ($value - $newMean) ** 2) / $newCount
            );

            $this->baseline[$name] = [
                'mean' => $newMean,
                'std' => $newStd,
                'count' => $newCount,
            ];

            if ($newStd > 0) {
                $zScore = abs(($value - $newMean) / $newStd);
                
                if ($zScore > $this->sensitivity) {
                    $anomalies[] = [
                        'metric' => $name,
                        'value' => $value,
                        'expected' => $newMean,
                        'z_score' => round($zScore, 2),
                    ];
                }
            }
        }

        return $anomalies;
    }

    public function reset(): void
    {
        $this->baseline = [];
    }

    public function getBaseline(): array
    {
        return $this->baseline;
    }
}

class AttackPredictor
{
    protected array $history = [];
    protected int $predictionWindow = 300;

    public function predict(string $ip): array
    {
        if (!isset($this->history[$ip])) {
            return ['prediction' => 'stable', 'confidence' => 0];
        }

        $events = $this->history[$ip];
        $now = time();
        
        $recentEvents = array_filter($events, fn($ts) => ($now - $ts) < $this->predictionWindow);
        $eventCount = count($recentEvents);

        $trends = $this->analyzeTrend($events);

        if ($eventCount > 50 && $trends === 'increasing') {
            return [
                'prediction' => 'attack_imminent',
                'confidence' => 85,
                'recommendation' => 'preemptive_block',
            ];
        }

        if ($eventCount > 20 && $trends === 'increasing') {
            return [
                'prediction' => 'suspicious_activity',
                'confidence' => 60,
                'recommendation' => 'increase_monitoring',
            ];
        }

        return [
            'prediction' => 'stable',
            'confidence' => 90,
            'recommendation' => 'normal',
        ];
    }

    public function recordEvent(string $ip): void
    {
        if (!isset($this->history[$ip])) {
            $this->history[$ip] = [];
        }
        
        $this->history[$ip][] = time();
        
        if (count($this->history[$ip]) > 1000) {
            $this->history[$ip] = array_slice($this->history[$ip], -500);
        }
    }

    protected function analyzeTrend(array $events): string
    {
        if (count($events) < 10) {
            return 'stable';
        }

        $recent = array_slice($events, -10);
        $older = array_slice($events, -20, 10);

        $recentRate = count($recent) / 10;
        $olderRate = count($older) / 10;

        if ($recentRate > $olderRate * 1.5) {
            return 'increasing';
        }

        if ($recentRate < $olderRate * 0.5) {
            return 'decreasing';
        }

        return 'stable';
    }
}