<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * List all registered routes with details
 */
class RouteDetailsCommand extends Command
{
    protected string $name = 'route:details';
    protected string $description = 'Show detailed information about routes';

    public function handle(Container $container, array $arguments): void
    {
        $this->info('📍 Route Details');
        $this->line('');

        $router = app(\Zenith\Routing\Router::class);
        $routes = $router->getRoutes();

        $this->line(sprintf("%-10s %-40s %-30s %-15s", 'Method', 'URI', 'Action', 'Middleware'));
        $this->line(str_repeat('-', 100));

        foreach ($routes as $route) {
            $method = $route->getMethod();
            $uri = $route->getUri();
            $action = is_string($route->getAction()) ? $route->getAction() : 'Closure';
            $middleware = implode(', ', $route->getMiddleware() ?? []);

            $this->line(sprintf("%-10s %-40s %-30s %-15s", $method, $uri, $action, $middleware));
        }

        $this->line('');
        $this->info("Total Routes: " . count($routes));
        $this->line('');
    }
}
