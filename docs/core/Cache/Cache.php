<?php

declare(strict_types=1);

namespace Zenith\Cache;

class Cache
{
    protected static string $driver = 'file';
    protected static string $path = '';

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::driver()->get(self::normalizeKey($key));

        if ($value === null) {
            return $default;
        }

        return unserialize($value);
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $serialized = serialize($value);
        return self::driver()->set(self::normalizeKey($key), $serialized, $ttl);
    }

    public static function forever(string $key, mixed $value): bool
    {
        return self::set($key, $value, 86400 * 365);
    }

    public static function has(string $key): bool
    {
        return self::driver()->has(self::normalizeKey($key));
    }

    public static function forget(string $key): bool
    {
        return self::driver()->forget(self::normalizeKey($key));
    }

    public static function flush(): bool
    {
        return self::driver()->flush();
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if (self::has($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function rememberForever(string $key, callable $callback): mixed
    {
        if (self::has($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::forever($key, $value);
        return $value;
    }

    public static function increment(string $key, int $value = 1): int
    {
        $current = (int) self::get($key, 0);
        $new = $current + $value;
        self::set($key, $new);
        return $new;
    }

    public static function decrement(string $key, int $value = 1): int
    {
        return self::increment($key, -$value);
    }

    protected static function normalizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }

    protected static function driver(): CacheDriver
    {
        $driver = self::$driver;

        return match ($driver) {
            'file' => new FileCacheDriver(self::getPath()),
            'array' => new ArrayCacheDriver(),
            default => throw new \RuntimeException("Cache driver [{$driver}] not supported"),
        };
    }

    public static function setDriver(string $driver): void
    {
        self::$driver = $driver;
    }

    public static function getPath(): string
    {
        if (self::$path === '') {
            self::$path = dirname(__DIR__, 2) . '/cache';
        }

        return self::$path;
    }

    public static function setPath(string $path): void
    {
        self::$path = $path;
    }
}

interface CacheDriver
{
    public function get(string $key): ?string;
    public function set(string $key, string $value, int $ttl): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
}

class FileCacheDriver implements CacheDriver
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key): ?string
    {
        $file = $this->path . '/' . $key . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $expiry = unserialize($content);

        if ($expiry['expiry'] < time()) {
            unlink($file);
            return null;
        }

        return $expiry['value'];
    }

    public function set(string $key, string $value, int $ttl): bool
    {
        $file = $this->path . '/' . $key . '.cache';
        $content = serialize([
            'expiry' => time() + $ttl,
            'value' => $value,
        ]);

        return file_put_contents($file, $content) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): bool
    {
        $file = $this->path . '/' . $key . '.cache';

        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    public function flush(): bool
    {
        $files = glob($this->path . '/*.cache');

        foreach ($files ?? [] as $file) {
            unlink($file);
        }

        return true;
    }
}

class ArrayCacheDriver implements CacheDriver
{
    protected static array $store = [];

    public function get(string $key): ?string
    {
        if (!isset(self::$store[$key])) {
            return null;
        }

        $item = self::$store[$key];

        if ($item['expiry'] < time()) {
            unset(self::$store[$key]);
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, string $value, int $ttl): bool
    {
        self::$store[$key] = [
            'value' => $value,
            'expiry' => time() + $ttl,
        ];

        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): bool
    {
        unset(self::$store[$key]);
        return true;
    }

    public function flush(): bool
    {
        self::$store = [];
        return true;
    }
}
