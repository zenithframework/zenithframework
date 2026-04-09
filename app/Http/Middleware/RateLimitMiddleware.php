<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;
use App\Services\RateLimitService;

class RateLimitMiddleware
{
    protected RateLimitService $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new RateLimitService();
    }

    public function handle(Request $request, callable $next, array $options = []): Response
    {
        $key = $this->getRateLimitKey($request);
        $maxAttempts = $options['max'] ?? 60;
        $decayMinutes = $options['decay'] ?? 1;

        if (!$this->rateLimiter->attempt($key, $maxAttempts, $decayMinutes)) {
            $retryAfter = $this->rateLimiter->getRetryAfter($key);
            
            return response(json_encode([
                'error' => 'Too Many Attempts.',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ]), 429)->header('Content-Type', 'application/json')
              ->header('Retry-After', (string)$retryAfter)
              ->header('X-RateLimit-Limit', (string)$maxAttempts)
              ->header('X-RateLimit-Remaining', '0');
        }

        $response = $next($request);
        
        $remaining = $this->rateLimiter->getRemainingAttempts($key, $maxAttempts, $decayMinutes);
        $response->header('X-RateLimit-Limit', (string)$maxAttempts);
        $response->header('X-RateLimit-Remaining', (string)$remaining);
        
        return $response;
    }

    protected function getRateLimitKey(Request $request): string
    {
        // Use API key if available
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return $apiKey;
        }
        
        // Fall back to IP address
        return $request->ip();
    }
}
