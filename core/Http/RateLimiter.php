<?php

declare(strict_types=1);

namespace Zen\Http;

class RateLimiter
{
    protected array $limits = [];
    protected int $decaySeconds = 60;

    public function for(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $this->decaySeconds = $decaySeconds;

        $config = config('ratelimit') ?? ['driver' => 'file'];
        $driver = match ($config['driver'] ?? 'file') {
            'redis' => new RedisRateDriver(),
            'database' => new DatabaseRateDriver(),
            default => new FileRateDriver(),
        };

        $attempts = $driver->getAttempts($key);
        
        if ($attempts >= $maxAttempts) {
            return false;
        }

        $driver->hit($key, $this->decaySeconds);
        return true;
    }

    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $config = config('ratelimit') ?? ['driver' => 'file'];
        $driver = match ($config['driver'] ?? 'file') {
            'redis' => new RedisRateDriver(),
            'database' => new DatabaseRateDriver(),
            default => new FileRateDriver(),
        };

        return $driver->getAttempts($key) >= $maxAttempts;
    }

    public function remaining(string $key, int $maxAttempts): int
    {
        $config = config('ratelimit') ?? ['driver' => 'file'];
        $driver = match ($config['driver'] ?? 'file') {
            'redis' => new RedisRateDriver(),
            'database' => new DatabaseRateDriver(),
            default => new FileRateDriver(),
        };

        $attempts = $driver->getAttempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    public function reset(string $key): void
    {
        $config = config('ratelimit') ?? ['driver' => 'file'];
        $driver = match ($config['driver'] ?? 'file') {
            'redis' => new RedisRateDriver(),
            'database' => new DatabaseRateDriver(),
            default => new FileRateDriver(),
        };

        $driver->reset($key);
    }

    public function availableIn(string $key): int
    {
        $config = config('ratelimit') ?? ['driver' => 'file'];
        $driver = match ($config['driver'] ?? 'file') {
            'redis' => new RedisRateDriver(),
            'database' => new DatabaseRateDriver(),
            default => new FileRateDriver(),
        };

        return $driver->availableIn($key);
    }
}

interface RateDriver
{
    public function getAttempts(string $key): int;
    public function hit(string $key, int $decaySeconds): void;
    public function reset(string $key): void;
    public function availableIn(string $key): int;
}

class FileRateDriver implements RateDriver
{
    protected string $path;

    public function __construct()
    {
        $this->path = dirname(__DIR__, 2) . '/storage/rate_limits';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    protected function getFile(string $key): string
    {
        $hash = md5($key);
        return $this->path . '/' . $hash . '.json';
    }

    public function getAttempts(string $key): int
    {
        $file = $this->getFile($key);
        
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        
        if ($data['expires_at'] < time()) {
            $this->reset($key);
            return 0;
        }

        return $data['attempts'] ?? 0;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        $file = $this->getFile($key);
        $attempts = $this->getAttempts($key);

        $data = [
            'attempts' => $attempts + 1,
            'expires_at' => time() + $decaySeconds,
        ];

        file_put_contents($file, json_encode($data));
    }

    public function reset(string $key): void
    {
        $file = $this->getFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function availableIn(string $key): int
    {
        $file = $this->getFile($key);
        
        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        return max(0, $data['expires_at'] - time());
    }
}

class DatabaseRateDriver implements RateDriver
{
    protected \Zen\Database\QueryBuilder $db;

    public function __construct()
    {
        $this->db = new \Zen\Database\QueryBuilder();
        $this->ensureTable();
    }

    protected function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            `key` TEXT PRIMARY KEY,
            attempts INTEGER DEFAULT 0,
            expires_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
        )";
        $this->db->raw($sql);
    }

    public function getAttempts(string $key): int
    {
        $record = $this->db->table('rate_limits')
            ->where('`key`', $key)
            ->first();

        if ($record === false || $record['expires_at'] < time()) {
            if ($record !== false) {
                $this->reset($key);
            }
            return 0;
        }

        return (int) $record['attempts'];
    }

    public function hit(string $key, int $decaySeconds): void
    {
        $attempts = $this->getAttempts($key);
        $expiresAt = time() + $decaySeconds;

        if ($attempts === 0) {
            $this->db->table('rate_limits')->insert([
                'key' => $key,
                'attempts' => 1,
                'expires_at' => $expiresAt,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        } else {
            $this->db->table('rate_limits')
                ->where('key', $key)
                ->update([
                    'attempts' => $attempts + 1,
                    'updated_at' => time(),
                ]);
        }
    }

    public function reset(string $key): void
    {
        $this->db->table('rate_limits')->where('key', $key)->delete();
    }

    public function availableIn(string $key): int
    {
        $record = $this->db->table('rate_limits')
            ->where('key', $key)
            ->first();

        if ($record === false) {
            return 0;
        }

        return max(0, (int) $record['expires_at'] - time());
    }
}

class RedisRateDriver implements RateDriver
{
    protected object|null $redis = null;

    public function __construct()
    {
        $redisClass = 'Redis';
        if (class_exists($redisClass)) {
            $config = config('ratelimit')['drivers']['redis'] ?? [];
            $this->redis = new $redisClass();
            try {
                $this->redis->connect($config['host'] ?? '127.0.0.1', (int) ($config['port'] ?? 6379));
            } catch (\Exception $e) {
                $this->redis = null;
            }
        }
    }

    public function getAttempts(string $key): int
    {
        if ($this->redis === null) {
            return 0;
        }

        $value = $this->redis->get("rate_limit:{$key}");
        if ($value === false || $value === null) {
            return 0;
        }

        $data = json_decode($value, true);
        if ($data['expires_at'] < time()) {
            $this->reset($key);
            return 0;
        }

        return (int) ($data['attempts'] ?? 0);
    }

    public function hit(string $key, int $decaySeconds): void
    {
        if ($this->redis === null) {
            return;
        }

        $attempts = $this->getAttempts($key);
        
        $this->redis->setex(
            "rate_limit:{$key}",
            $decaySeconds,
            json_encode([
                'attempts' => $attempts + 1,
                'expires_at' => time() + $decaySeconds,
            ])
        );
    }

    public function reset(string $key): void
    {
        if ($this->redis !== null) {
            $this->redis->del("rate_limit:{$key}");
        }
    }

    public function availableIn(string $key): int
    {
        if ($this->redis === null) {
            return 0;
        }

        $value = $this->redis->get("rate_limit:{$key}");
        if ($value === false || $value === null) {
            return 0;
        }

        $data = json_decode($value, true);
        return max(0, (int) $data['expires_at'] - time());
    }
}

class ThrottleRequests
{
    public function handle(\Zen\Http\Request $request, \Closure $next, int $maxAttempts = 60, int $decaySeconds = 60): \Zen\Http\Response
    {
        $key = $this->resolveRequestSignature($request);
        $limiter = new RateLimiter();

        if (!$limiter->for($key, $maxAttempts, $decaySeconds)) {
            $retryAfter = $limiter->availableIn($key);
            
            return response('', 429, [
                'Retry-After' => (string) $retryAfter,
                'X-RateLimit-Limit' => (string) $maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        $response = $next($request);
        
        return $response->withHeader('X-RateLimit-Limit', (string) $maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $limiter->remaining($key, $maxAttempts));
    }

    protected function resolveRequestSignature(\Zen\Http\Request $request): string
    {
        $ip = $request->ip ?? 'unknown';
        return 'throttle_' . md5($ip . $request->uri);
    }
}