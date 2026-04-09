<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class RouteFix extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $router = $container->make(\Zenith\Routing\Router::class);
        $routes = $router->getRoutes();
        
        $fixed = 0;
        $issues = [];

        foreach ($routes as $index => $route) {
            $handler = $route->getHandler();
            
            if ($handler instanceof \Closure) {
                $issues[] = "Route #{$index}: Closure found in route definition - should use controller";
                $fixed++;
            }
            
            if (is_string($handler) && str_contains($handler, '->')) {
                $issues[] = "Route #{$index}: String handler contains arrow notation - convert to Class@method";
                $fixed++;
            }
            
            $uri = $route->getUri();
            if (str_starts_with($uri, '/') && strlen($uri) > 1) {
                $uri = rtrim($uri, '/');
                if ($uri !== $route->getUri()) {
                    $issues[] = "Route #{$index}: Trailing slash removed from {$route->getUri()}";
                }
            }
        }

        if (empty($issues)) {
            $this->info("✓ No route issues found.");
        } else {
            foreach ($issues as $issue) {
                $this->warn("⚠ {$issue}");
            }
            $this->line("\nTotal issues: " . count($issues));
            $this->info("Run 'php zen route:convert' to convert closures to controllers.");
        }
    }
}