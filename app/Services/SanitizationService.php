<?php

declare(strict_types=1);

namespace App\Services;

class SanitizationService
{
    public function sanitizeString(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Strip tags
        $input = strip_tags($input);
        
        // Convert special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($input);
    }

    public function sanitizeHtml(string $input, array $allowedTags = [], array $allowedAttrs = []): string
    {
        if (empty($allowedTags)) {
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'img', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre'];
        }
        
        if (empty($allowedAttrs)) {
            $allowedAttrs = ['href', 'src', 'alt', 'title', 'class', 'id', 'target', 'rel'];
        }

        // Strip tags not in allowed list
        $pattern = '@<(?!(?:' . implode('|', $allowedTags) . ')\b)[^>]+>@i';
        $input = preg_replace($pattern, '', $input);
        
        // Remove attributes not in allowed list
        $pattern = '@\s+(?!' . implode('|', $allowedAttrs) . ')=["\'][^"\']*["\']@i';
        $input = preg_replace($pattern, '', $input);
        
        // Remove event handlers
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        // Remove javascript: protocol
        $input = preg_replace('/javascript\s*:/i', '', $input);

        return trim($input);
    }

    public function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }

    public function sanitizeUrl(string $url): string
    {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }

    public function sanitizeInt(mixed $input): int
    {
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT, FILTER_NULL_ON_FAILURE) ?? 0;
    }

    public function sanitizeFloat(mixed $input): float
    {
        return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_NULL_ON_FAILURE) ?? 0.0;
    }

    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = session()->get('_token');
        return $sessionToken && hash_equals($sessionToken, $token);
    }

    public function generateCsrfToken(): string
    {
        if (!session()->has('_token')) {
            session()->put('_token', bin2hex(random_bytes(32)));
        }
        return session()->get('_token');
    }

    public function sanitizeArray(array $input, array $rules = []): array
    {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                $sanitized[$key] = $this->applyRule($value, $rule);
            } else {
                // Default sanitization
                if (is_string($value)) {
                    $sanitized[$key] = $this->sanitizeString($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }
        
        return $sanitized;
    }

    protected function applyRule(mixed $value, string $rule): mixed
    {
        return match($rule) {
            'string' => $this->sanitizeString((string)$value),
            'email' => $this->sanitizeEmail((string)$value),
            'url' => $this->sanitizeUrl((string)$value),
            'int' => $this->sanitizeInt($value),
            'float' => $this->sanitizeFloat($value),
            'html' => $this->sanitizeHtml((string)$value),
            default => $value,
        };
    }
}
