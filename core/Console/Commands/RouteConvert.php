<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class RouteConvert extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $router = $container->make(\Zen\Routing\Router::class);
        $routes = $router->getRoutes();
        
        $converted = 0;
        
        foreach ($routes as $route) {
            $handler = $route->getHandler();
            
            if ($handler instanceof \Closure) {
                $this->warn("Cannot auto-convert Closure at {$route->getMethod()} {$route->getUri()}");
                $this->line("  Manual conversion required - create a controller method");
                continue;
            }
            
            if (is_string($handler) && !str_contains($handler, '@') && !str_contains($handler, '\\')) {
                $this->info("String handler '{$handler}' at {$route->getMethod()} {$route->getUri()}");
                $converted++;
            }
        }
        
        $this->info("Conversion analysis complete.");
        $this->line("Note: Use 'php zen make:controller <Name>' to create controllers for closure routes.");
    }
}