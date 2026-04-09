<?php

declare(strict_types=1);

namespace Zenith;

abstract class Facade
{
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = app(static::getFacadeAccessor());
        return $instance->{$method}(...$args);
    }

    abstract protected static function getFacadeAccessor(): string;
}
