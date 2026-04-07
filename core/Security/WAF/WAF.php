<?php

declare(strict_types=1);

namespace Zen\Security\WAF;

use Zen\Security\Firewall\IPBlocker;

class WAF
{
    protected array $rules = [];
    protected array $sanitizers = [];
    protected bool $enabled;
    protected bool $logOnly;

    public function __construct()
    {
        $this->enabled = config('security.waf')['enabled'] ?? true;
        $this->logOnly = config('security.waf')['log_only'] ?? false;
        $this->loadRules();
    }

    protected function loadRules(): void
    {
        $this->rules = [
            'sql_injection' => [
                'pattern' => '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|TRUNCATE)\b)|(--)|(\/\*)|(\*\/)/
i',
                'score' => 100,
                'action' => 'block',
            ],
            'xss_basic' => [
                'pattern' => '/<script|javascript:|on(load|error|click|mouse)/i',
                'score' => 80,
                'action' => 'block',
            ],
            'xss_attr' => [
                'pattern' => '/(\bon\w+\s*=)|(expression\s*\()/i',
                'score' => 70,
                'action' => 'block',
            ],
            'path_traversal' => [
                'pattern' => '/(\.\.\/)|(\.\.\\)|(%2e%2e%2f)|(%2e%2e\/)/i',
                'score' => 60,
                'action' => 'block',
            ],
            'command_injection' => [
                'pattern' => '/(;\s*\/bin\/sh)|(\|\s*cat)|(\&\&.*rm)|(passwd)|(shadow)/i',
                'score' => 100,
                'action' => 'block',
            ],
            'ldap_injection' => [
                'pattern' => '/(\*\)|(\(\!\(attribute=))|(\(\d+=))/i',
                'score' => 80,
                'action' => 'block',
            ],
            'xml_injection' => [
                'pattern' => '/(<!DOCTYPE|<!ENTITY|<!\[CDATA)/i',
                'score' => 50,
                'action' => 'sanitize',
            ],
            'null_byte' => [
                'pattern' => '/(\x00)/',
                'score' => 30,
                'action' => 'sanitize',
            ],
            'base64' => [
                'pattern' => '/^[A-Za-z0-9+\/]+=*$/',
                'score' => 20,
                'action' => 'log',
            ],
            'suspicious_extension' => [
                'pattern' => '/\.(php\d?|phtml|phar|phpt|asp|aspx|jsp|exe|sh|cgi|pl|py|rb|env|git|config)$/i',
                'score' => 40,
                'action' => 'block',
            ],
        ];
    }

    public function check(array $request): array
    {
        if (!$this->enabled) {
            return ['allowed' => true, 'violations' => []];
        }

        $violations = [];
        $totalScore = 0;

        $uri = $request['uri'] ?? '';
        $query = $request['query'] ?? '';
        $body = $request['body'] ?? '';
        $headers = $request['headers'] ?? [];

        $inputs = [
            'uri' => $uri,
            'query' => $query,
            'body' => is_array($body) ? json_encode($body) : (string) $body,
        ];

        foreach ($inputs as $source => $input) {
            if (empty($input)) {
                continue;
            }

            foreach ($this->rules as $name => $rule) {
                if (preg_match($rule['pattern'], $input)) {
                    $violations[] = [
                        'rule' => $name,
                        'source' => $source,
                        'score' => $rule['score'],
                        'action' => $rule['action'],
                        'value' => $this->sanitizeForLog($input),
                    ];

                    $totalScore += $rule['score'];
                }
            }
        }

        foreach ($headers as $name => $value) {
            foreach ($this->rules as $rule) {
                if (preg_match($rule['pattern'], (string) $value)) {
                    $violations[] = [
                        'rule' => 'header_injection',
                        'source' => 'header:' . $name,
                        'score' => $rule['score'],
                        'action' => $rule['action'],
                    ];
                    $totalScore += $rule['score'];
                }
            }
        }

        if ($totalScore > 0) {
            $this->logViolation($request['ip'] ?? 'unknown', $violations, $totalScore);
        }

        $shouldBlock = $totalScore > 80;
        $shouldSanitize = $totalScore > 30 && !$shouldBlock;

        if ($shouldBlock) {
            return [
                'allowed' => false,
                'violations' => $violations,
                'action' => 'block',
                'score' => $totalScore,
                'reason' => 'WAF detected malicious request',
            ];
        }

        if ($this->logOnly && $totalScore > 0) {
            return [
                'allowed' => true,
                'violations' => $violations,
                'action' => 'log',
                'score' => $totalScore,
            ];
        }

        return [
            'allowed' => true,
            'violations' => $violations,
            'action' => $shouldSanitize ? 'sanitize' : 'allow',
            'score' => $totalScore,
        ];
    }

    public function sanitize(array $request): array
    {
        $sanitized = $request;

        $sanitizers = [
            'html' => 'htmlspecialchars',
            'url' => 'urlencode',
            'base64' => fn($v) => base64_decode($v, true) ? '[BASE64]' : $v,
            'null' => fn($v) => str_replace("\0", '', $v),
            'trim' => 'trim',
        ];

        foreach ($request as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = str_replace(
                    ['<script', 'javascript:', 'onclick', 'onerror'],
                    ['<scr' . 'ipt', 'java script:', 'onclick disabled', 'onerror disabled'],
                    $value
                );
            }
        }

        return $sanitized;
    }

    protected function sanitizeForLog(string $value): string
    {
        if (strlen($value) > 100) {
            return substr($value, 0, 100) . '...[truncated]';
        }
        return $value;
    }

    protected function logViolation(string $ip, array $violations, int $score): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/security/waf';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/violations.json';
        $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

        $logs[] = [
            'ip' => $ip,
            'timestamp' => time(),
            'violations' => $violations,
            'score' => $score,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
        ];

        $logs = array_slice($logs, -1000);

        file_put_contents($logFile, json_encode($logs));

        if ($score > 80) {
            $blocker = new IPBlocker();
            $blocker->recordViolation($ip, 'waf');
        }
    }

    public function addRule(string $name, string $pattern, int $score, string $action): void
    {
        $this->rules[$name] = [
            'pattern' => $pattern,
            'score' => $score,
            'action' => $action,
        ];
    }

    public function removeRule(string $name): void
    {
        unset($this->rules[$name]);
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getLogs(int $limit = 100): array
    {
        $logFile = dirname(__DIR__, 2) . '/storage/security/waf/violations.json';
        if (!file_exists($logFile)) {
            return [];
        }

        $logs = json_decode(file_get_contents($logFile), true) ?? [];
        return array_slice($logs, -$limit);
    }
}

class RequestFilter
{
    protected array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
    protected array $allowedContentTypes = ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'];
    protected int $maxBodySize = 10485760;
    protected array $requiredHeaders = [];

    public function filter(array $request): array
    {
        $errors = [];

        if (!in_array($request['method'] ?? 'GET', $this->allowedMethods)) {
            $errors[] = 'Method not allowed';
        }

        if (isset($request['headers']['Content-Type'])) {
            $contentType = $request['headers']['Content-Type'];
            $isValid = false;
            foreach ($this->allowedContentTypes as $allowed) {
                if (str_starts_with($contentType, $allowed)) {
                    $isValid = true;
                    break;
                }
            }
            if (!$isValid) {
                $errors[] = 'Invalid content type';
            }
        }

        if (isset($request['body_size']) && $request['body_size'] > $this->maxBodySize) {
            $errors[] = 'Request body too large';
        }

        foreach ($this->requiredHeaders as $header) {
            if (!isset($request['headers'][$header])) {
                $errors[] = "Required header missing: {$header}";
            }
        }

        return $errors;
    }

    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = array_map('strtoupper', $methods);
    }

    public function setMaxBodySize(int $bytes): void
    {
        $this->maxBodySize = $bytes;
    }

    public function addRequiredHeader(string $header): void
    {
        $this->requiredHeaders[] = $header;
    }
}