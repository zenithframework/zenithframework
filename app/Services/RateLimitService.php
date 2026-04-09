<?php

declare(strict_types=1);

namespace App\Services;

use Zen\Cache\Cache;

class RateLimitService
{
    protected Cache $cache;

    public function __construct()
    {
        $this->cache = new Cache();
    }

    public function attempt(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $key = "rate_limit:{$key}";
        $attempts = $this->cache->get($key, 0);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        $this->cache->put($key, $attempts + 1, $decayMinutes);
        return true;
    }

    public function clear(string $key): void
    {
        $this->cache->delete("rate_limit:{$key}");
    }

    public function getRemainingAttempts(string $key, int $maxAttempts, int $decayMinutes): int
    {
        $attempts = $this->cache->get("rate_limit:{$key}", 0);
        return max(0, $maxAttempts - $attempts);
    }

    public function getRetryAfter(string $key): int
    {
        return $this->cache->get("rate_limit:{$key}:retry_after", 0);
    }

    // Predefined rate limits
    public function loginAttempt(string $ip): bool
    {
        return $this->attempt("login:{$ip}", 5, 15); // 5 attempts per 15 minutes
    }

    public function apiRequest(string $apiKey): bool
    {
        return $this->attempt("api:{$apiKey}", 100, 1); // 100 requests per minute
    }

    public function uploadFile(string $userId): bool
    {
        return $this->attempt("upload:{$userId}", 10, 60); // 10 uploads per hour
    }

    public function sendEmail(string $userId): bool
    {
        return $this->attempt("email:{$userId}", 5, 60); // 5 emails per hour
    }
}
