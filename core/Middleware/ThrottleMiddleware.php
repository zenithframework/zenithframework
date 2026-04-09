<?php

declare(strict_types=1);

namespace Zenith\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Closure;

class ThrottleMiddleware implements MiddlewareInterface
{
    protected int $maxAttempts = 60;
    protected int $decaySeconds = 60;
    protected string $prefix = 'throttle';

    public function handle(Request $request, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $ip = $request->ip();
        
        $storageKey = $this->prefix . ':' . ($key ?: $ip);
        
        $data = $this->getRateLimitData($storageKey);
        
        if ($data === null) {
            $data = ['attempts' => 0, 'reset_at' => time() + $this->decaySeconds];
        }
        
        if (time() > $data['reset_at']) {
            $data = ['attempts' => 0, 'reset_at' => time() + $this->decaySeconds];
        }
        
        $data['attempts']++;
        
        if ($data['attempts'] > $this->maxAttempts) {
            $response = $next($request);
            $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
            $response->setHeader('X-RateLimit-Remaining', '0');
            $response->setHeader('X-RateLimit-Reset', (string) $data['reset_at']);
            $response->setStatusCode(429);
            return $response;
        }
        
        $this->storeRateLimitData($storageKey, $data);
        
        $response = $next($request);
        
        $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string) ($this->maxAttempts - $data['attempts']));
        $response->setHeader('X-RateLimit-Reset', (string) $data['reset_at']);
        
        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = auth()->user()) {
            return 'user:' . $user->id ?? 'guest';
        }
        
        return '';
    }

    protected function getRateLimitData(string $key): ?array
    {
        $file = $this->getStoragePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        return $data;
    }

    protected function storeRateLimitData(string $key, array $data): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/rate_limits';
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $file = $this->getStoragePath($key);
        file_put_contents($file, json_encode($data));
    }

    protected function getStoragePath(string $key): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/rate_limits';
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . md5($key) . '.json';
    }

    public function for(int $maxAttempts, int $decaySeconds = 60): self
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
        return $this;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }
}
