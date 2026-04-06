<?php

declare(strict_types=1);

namespace Zen\Boot;

use Zen\Container;
use Zen\Routing\Router;
use Zen\Routing\Route;

class RouteLoader
{
    protected array $routeFiles = [
        'Web' => '/',
        'Api' => '/api',
        'Auth' => '/auth',
        'Ai' => '/ai',
    ];

    public function register(): void
    {
        $routesDir = __DIR__ . '/../routes/';
        $container = app();

        if (!$container->has(Router::class)) {
            $container->singleton(Router::class, fn() => new Router());
        }

        $router = $container->make(Router::class);

        foreach ($this->routeFiles as $name => $prefix) {
            $file = $routesDir . $name . '.php';

            if (file_exists($file)) {
                $this->loadRouteFile($router, $file, $prefix);
            }
        }

        $container->instance(Router::class, $router);
    }

    protected function loadRouteFile(Router $router, string $file, string $prefix): void
    {
        $router->group($prefix, function (Router $router) use ($file) {
            require $file;
        });
    }
}
