<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void put(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void forget(string $key)
 * @method static void flash(string $key, mixed $value)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static void regenerateToken()
 * @method static string token()
 * @method static array all()
 * @method static void save()
 *
 * @see \Zenith\Session\Session
 */
class Session extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Session\Session::class;
    }
}
