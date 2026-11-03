<?php

declare(strict_types=1);

namespace Zenith\Security\WAF;

use Zenith\Security\Firewall\IPBlocker;

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
                'pattern' => '/(\bUNION\b[\s\S]{0,20}\bSELECT\b)|(\bSELECT\b[\s\S]{0,60}\bFROM\b)|(\bINSERT\s+INTO\b)|(\bUPDATE\b[\s\S]{0,40}\bSET\b)|(\bDELETE\s+FROM\b)|(\bDROP\s+(TABLE|DATABASE)\b)|(\bALTER\s+TABLE\b)|(\bTRUNCATE\s+TABLE\b)|(\bSLEEP\s*\()|(\bBENCHMARK\s*\()|(\bLOAD_FILE\s*\()|(\bINFORMATION_SCHEMA\b)|(\bOR\b\s+\d+\s*=\s*\d+)|(\'\s*OR\s*\')|(--\s)|(\/\*[\s\S]*?\*\/)/i',
                'score' => 100,
                'action' => 'block',
            ],
            'xss_basic' => [
                'pattern' => '/(<script|<\s*\/?\s*(iframe|object|embed|svg|math)|javascript\s*:|vbscript\s*:|data\s*:text\/html|on(load|error|click|mouse|focus|blur|submit|change|input|key)\s*=)/i',
                'score' => 80,
                'action' => 'block',
            ],
            'xss_attr' => [
                'pattern' => '/(\bon\w+\s*=)|(expression\s*\()|(url\s*\(\s*[' . '\'"]?\s*javascript)/i',
                'score' => 70,
                'action' => 'block',
            ],
            'path_traversal' => [
                'pattern' => '/(\.\.\/)|(\.\.\\\\)|(%2e%2e%2f)|(%2e%2e\/)|(%252e%252e)|(\.\.%2f)|(%2e%2e%5c)/i',
                'score' => 60,
                'action' => 'block',
            ],
            'command_injection' => [
                'pattern' => '/(;\s*(\/bin\/|sh\b|bash\b|cmd\b|powershell|whoami|id\b|cat\b|wget\b|curl\b))|(\|\s*(cat|nc|bash|sh|whoami|id)\b)|(\$\([^)]*\))|(`[^`]*`)|(\|\||&&\s*(rm|wget|curl|nc|sh|bash)\b)/i',
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
                'pattern' => '/(\x00)|(%00)/',
                'score' => 30,
                'action' => 'sanitize',
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

        // Normalize a copy of the aggregate input to defeat trivial obfuscation
        // (URL-encoding, doubled encoding, whitespace/comment splitting) before
        // matching. The original is still reported for context.
        $decoded = rawurldecode($uri . ' ' . $query . ' ' . ($inputs['body'] ?? ''));
        $decoded = rawurldecode($decoded);
        $inputs['decoded'] = preg_replace('/\s+/', ' ', $decoded);

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
            foreach ($this->rules as $ruleName => $rule) {
                if (preg_match($rule['pattern'], (string) $value)) {
                    $violations[] = [
                        'rule' => $ruleName,
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

        $entry = [
            'ip' => $ip,
            'timestamp' => time(),
            'violations' => $violations,
            'score' => $score,
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
        ];

        // Use an exclusive lock so concurrent requests cannot corrupt or lose
        // log entries, and cap the file size to avoid unbounded growth/DoS.
        $handle = fopen($logFile, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }

            $size = filesize($logFile) ?: 0;
            $logs = $size > 0 ? (json_decode(stream_get_contents($handle), true) ?: []) : [];

            $logs[] = $entry;
            $logs = array_slice($logs, -1000);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($logs));

            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

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