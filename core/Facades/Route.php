<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static string to(string $route, array $parameters = [])
 * @method static string action(string|array $action, array $parameters = [])
 * @method static string current()
 * @method static string full()
 * @method static string secure(string $route, array $parameters = [])
 * @method static string asset(string $path, bool $secure = null)
 * @method static string route(string $name, array $parameters = [], bool $absolute = false)
 * @method static bool has(string $name)
 * @method static array getRoutes()
 *
 * @see \Zenith\Routing\Router
 */
class Route extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'router';
    }
}
