<?php

declare(strict_types=1);

namespace Zen\Cache;

class MultiLevelCache
{
    protected array $levels = [];
    protected array $config = [];
    protected string $defaultLevel = 'memory';

    public function __construct()
    {
        $this->config = config('cache') ?? ['default' => 'file'];
        $this->initLevels();
    }

    protected function initLevels(): void
    {
        $this->levels = [
            'memory' => new MemoryCache(),
            'redis' => $this->initRedis(),
            'apc' => new ApcuCache(),
        ];
    }

    protected function initRedis(): ?RedisCache
    {
        if (!class_exists('Redis')) {
            return null;
        }

        $config = $this->config['redis'] ?? [];
        return new RedisCache($config);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        foreach (['memory', 'file', 'redis'] as $level) {
            if (!isset($this->levels[$level])) {
                continue;
            }

            $value = $this->levels[$level]->get($key);
            
            if ($value !== null) {
                if ($level !== 'memory') {
                    $this->levels['memory']->set($key, $value, 60);
                }
                return $value;
            }
        }

        return $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->levels['memory']->set($key, $value, min($ttl, 60));
        $this->levels['file']->set($key, $value, $ttl);

        if (isset($this->levels['redis'])) {
            $this->levels['redis']->set($key, $value, $ttl);
        }

        return true;
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, callable $callback): mixed
    {
        return $this->remember($key, $callback, 31536000);
    }

    public function delete(string $key): bool
    {
        foreach ($this->levels as $level) {
            $level->delete($key);
        }

        return true;
    }

    public function clear(): void
    {
        foreach ($this->levels as $level) {
            $level->clear();
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key, null) !== null;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    public function getStats(): array
    {
        $stats = [];
        
        foreach ($this->levels as $name => $level) {
            if (method_exists($level, 'getStats')) {
                $stats[$name] = $level->getStats();
            }
        }

        return $stats;
    }
}

class MemoryCache
{
    protected array $cache = [];
    protected array $expiry = [];

    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        if (isset($this->expiry[$key]) && $this->expiry[$key] < time()) {
            unset($this->cache[$key], $this->expiry[$key]);
            return null;
        }

        return $this->cache[$key];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->cache[$key] = $value;
        
        if ($ttl > 0) {
            $this->expiry[$key] = time() + $ttl;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expiry[$key]);
        return true;
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->expiry = [];
    }

    public function getStats(): array
    {
        $this->cleanup();
        
        return [
            'items' => count($this->cache),
            'memory_estimate' => strlen(serialize($this->cache)),
        ];
    }

    protected function cleanup(): void
    {
        $now = time();
        
        foreach ($this->expiry as $key => $time) {
            if ($time < $now) {
                unset($this->cache[$key], $this->expiry[$key]);
            }
        }
    }
}

class ApcuCache
{
    public function get(string $key): mixed
    {
        if (!function_exists('apcu_fetch')) {
            return null;
        }
        
        $result = @apcu_fetch($key);
        return $result !== false ? $result : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!function_exists('apcu_store')) {
            return false;
        }
        return @apcu_store($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        if (!function_exists('apcu_delete')) {
            return false;
        }
        return @apcu_delete($key);
    }

    public function clear(): bool
    {
        if (!function_exists('apcu_clear_cache')) {
            return false;
        }
        return @apcu_clear_cache();
    }

    public function getStats(): array
    {
        if (!function_exists('apcu_cache_info')) {
            return [];
        }
        
        $info = @apcu_cache_info();
        return [
            'hits' => $info['num_hits'] ?? 0,
            'misses' => $info['num_misses'] ?? 0,
            'memory' => $info['mem_size'] ?? 0,
        ];
    }
}

class RedisCache
{
    protected ?object $redis = null;
    protected string $prefix = 'zen:';

    public function __construct(array $config = [])
    {
        if (!class_exists('Redis') || empty($config)) {
            return;
        }
        
        try {
            $this->redis = new \Redis();
            $this->redis->connect($config['host'] ?? '127.0.0.1', $config['port'] ?? 6379);
            $this->prefix = $config['prefix'] ?? 'zen:';
        } catch (\Exception $e) {
            $this->redis = null;
        }
    }

    public function get(string $key): mixed
    {
        if ($this->redis === null) {
            return null;
        }

        $value = $this->redis->get($this->prefix . $key);
        return $value !== false ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if ($this->redis === null) {
            return false;
        }

        return $this->redis->setex($this->prefix . $key, $ttl, serialize($value));
    }

    public function delete(string $key): bool
    {
        if ($this->redis === null) {
            return false;
        }

        return $this->redis->del($this->prefix . $key) > 0;
    }

    public function clear(): bool
    {
        if ($this->redis === null) {
            return false;
        }

        $keys = $this->redis->keys($this->prefix . '*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
        
        return true;
    }

    public function getStats(): array
    {
        if ($this->redis === null) {
            return ['status' => 'not connected'];
        }

        return [
            'connected' => true,
            'db_size' => $this->redis->dbSize(),
        ];
    }
}

class CacheWarmer
{
    protected MultiLevelCache $cache;

    public function __construct()
    {
        $this->cache = new MultiLevelCache();
    }

    public function warm(array $items): void
    {
        foreach ($items as $key => $value) {
            $ttl = is_array($value) ? ($value['ttl'] ?? 3600) : 3600;
            $val = is_array($value) ? ($value['value'] ?? $value) : $value;
            
            $this->cache->set($key, $val, $ttl);
        }
    }

    public function warmFromConfig(string $configKey = 'cache.warm'): void
    {
        $items = config($configKey, []);
        
        if (!empty($items)) {
            $this->warm($items);
        }
    }

    public function warmOnDemand(callable $generator): void
    {
        $items = $generator();
        
        if (is_array($items)) {
            $this->warm($items);
        }
    }
}

class CacheCoalescing
{
    protected static array $pending = [];
    protected static int $window = 100;

    public static function get(string $key, callable $callback): mixed
    {
        if (isset(self::$pending[$key])) {
            return self::$pending[$key];
        }

        self::$pending[$key] = $callback();

        if (count(self::$pending) > 100) {
            self::$pending = array_slice(self::$pending, -50);
        }

        return self::$pending[$key];
    }

    public static function clear(string $key): void
    {
        unset(self::$pending[$key]);
    }

    public static function clearAll(): void
    {
        self::$pending = [];
    }
}