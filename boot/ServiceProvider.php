<?php

declare(strict_types=1);

namespace Zenith\Boot;

use Zenith\Container;

abstract class ServiceProvider
{
    protected static ?Container $container = null;

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    abstract public function register(): void;

    abstract public function boot(): void;

    protected function app(): Container
    {
        return self::$container ?? throw new \RuntimeException('Container not set');
    }
}
