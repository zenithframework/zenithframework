<?php

declare(strict_types=1);

namespace Zenith\Security\Audit;

class AuditLogger
{
    protected string $logPath;
    protected array $channels = ['security', 'auth', 'api', 'system'];
    protected int $retentionDays = 30;

    public function __construct()
    {
        $this->logPath = dirname(__DIR__, 2) . '/storage/security/audit';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function log(string $event, array $data = []): void
    {
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'data' => $data,
        ];

        $channel = $this->getChannel($event);
        $file = $this->logPath . '/' . $channel . '_' . date('Y-m-d') . '.json';

        $existing = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $existing[] = $entry;

        file_put_contents($file, json_encode($existing));
    }

    protected function getChannel(string $event): string
    {
        if (str_contains($event, 'auth') || str_contains($event, 'login') || str_contains($event, 'logout')) {
            return 'auth';
        }
        if (str_contains($event, 'api') || str_contains($event, 'request')) {
            return 'api';
        }
        if (str_contains($event, 'security') || str_contains($event, 'block') || str_contains($event, 'attack')) {
            return 'security';
        }
        return 'system';
    }

    public function auth(string $event, array $data = []): void
    {
        $this->log('auth.' . $event, $data);
    }

    public function security(string $event, array $data = []): void
    {
        $this->log('security.' . $event, $data);
    }

    public function api(string $event, array $data = []): void
    {
        $this->log('api.' . $event, $data);
    }

    public function getLogs(string $channel = 'security', ?string $date = null, int $limit = 100): array
    {
        $date = $date ?? date('Y-m-d');
        $file = $this->logPath . '/' . $channel . '_' . $date . '.json';

        if (!file_exists($file)) {
            return [];
        }

        $logs = json_decode(file_get_contents($file), true) ?? [];
        return array_slice($logs, -$limit);
    }

    public function search(array $criteria, int $limit = 100): array
    {
        $results = [];
        
        foreach ($this->channels as $channel) {
            for ($i = 0; $i < $this->retentionDays; $i++) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $file = $this->logPath . '/' . $channel . '_' . $date . '.json';
                
                if (!file_exists($file)) {
                    continue;
                }
                
                $logs = json_decode(file_get_contents($file), true) ?? [];
                
                foreach ($logs as $log) {
                    if ($this->matches($log, $criteria)) {
                        $results[] = $log;
                        
                        if (count($results) >= $limit) {
                            return $results;
                        }
                    }
                }
            }
        }
        
        return $results;
    }

    protected function matches(array $log, array $criteria): bool
    {
        foreach ($criteria as $key => $value) {
            if (!isset($log[$key])) {
                return false;
            }
            
            if (is_array($value)) {
                if (!in_array($log[$key], $value)) {
                    return false;
                }
            } elseif ($log[$key] !== $value) {
                return false;
            }
        }
        
        return true;
    }

    public function cleanup(): void
    {
        $cutoff = strtotime("-{$this->retentionDays} days");
        
        foreach (glob($this->logPath . '/*.json') as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }

    public function getStats(?string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stats = [];
        
        foreach ($this->channels as $channel) {
            $file = $this->logPath . '/' . $channel . '_' . $date . '.json';
            $logs = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
            
            $stats[$channel] = [
                'count' => count($logs),
                'events' => [],
            ];
            
            foreach ($logs as $log) {
                $event = $log['event'] ?? 'unknown';
                $stats[$channel]['events'][$event] = ($stats[$channel]['events'][$event] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
}

class AlertManager
{
    protected array $channels = [];
    protected array $rules = [];

    public function __construct()
    {
        $this->channels = [
            'email' => config('security.alerts.email') ?? null,
            'slack' => config('security.alerts.slack') ?? null,
            'telegram' => config('security.alerts.telegram') ?? null,
        ];
    }

    public function addRule(string $name, array $rule): void
    {
        $this->rules[$name] = $rule;
    }

    public function trigger(string $event, array $data): void
    {
        foreach ($this->rules as $name => $rule) {
            if ($this->shouldAlert($event, $data, $rule)) {
                $this->send($name, $event, $data);
            }
        }
    }

    protected function shouldAlert(string $event, array $data, array $rule): bool
    {
        if (!empty($rule['events']) && !in_array($event, $rule['events'])) {
            return false;
        }

        if (!empty($rule['threshold'])) {
            $recentCount = $this->getRecentCount($event);
            if ($recentCount < $rule['threshold']) {
                return false;
            }
        }

        return true;
    }

    protected function getRecentCount(string $event): int
    {
        $logger = new AuditLogger();
        $logs = $logger->getLogs('security', date('Y-m-d'), 1000);
        
        $count = 0;
        foreach ($logs as $log) {
            if (($log['event'] ?? '') === $event) {
                $count++;
            }
        }
        
        return $count;
    }

    protected function send(string $channel, string $event, array $data): void
    {
        $message = $this->formatMessage($event, $data);
        
        if ($this->channels['email'] ?? null) {
            $this->sendEmail($this->channels['email'], $event, $message);
        }
        
        if ($this->channels['slack'] ?? null) {
            $this->sendSlack($this->channels['slack'], $message);
        }
        
        if ($this->channels['telegram'] ?? null) {
            $this->sendTelegram($this->channels['telegram'], $message);
        }
    }

    protected function formatMessage(string $event, array $data): string
    {
        $ip = $data['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $data['uri'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        return "🔒 Security Alert: {$event}\n\nIP: {$ip}\nURI: {$uri}\nTime: " . date('Y-m-d H:i:s');
    }

    protected function sendEmail(string $to, string $subject, string $message): void
    {
        app(\Zenith\Mail\Mailer::class)->raw($to, $subject, $message);
    }

    protected function sendSlack(string $webhook, string $message): void
    {
        if (filter_var($webhook, FILTER_VALIDATE_URL)) {
            $ch = curl_init($webhook);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['text' => $message]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    protected function sendTelegram(string $config, string $message): void
    {
    }
}