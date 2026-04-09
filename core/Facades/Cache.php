<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value, int $ttl = 0)
 * @method static bool has(string $key)
 * @method static bool forget(string $key)
 * @method static void clear()
 * @method static mixed remember(string $key, int $ttl, \Closure $callback)
 * @method static void forever(string $key, mixed $value)
 * @method static \Zenith\Cache\Cache driver(string|null $name = null)
 *
 * @see \Zenith\Cache\Cache
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Cache\Cache::class;
    }
}
