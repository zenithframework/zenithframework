<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Http\Request;

class RouteTest extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        if (!isset($arguments[0])) {
            $this->error("Usage: php zen route:test <uri> [method]");
            return;
        }

        $uri = $arguments[0];
        $method = $arguments[1] ?? 'GET';

        $router = $container->make(\Zen\Routing\Router::class);
        
        $request = Request::create($method, $uri);

        $route = $router->match($request);

        if ($route) {
            $this->info("✓ Route found:");
            $this->line("  Method: {$route->getMethod()}");
            $this->line("  URI: {$route->getUri()}");
            $this->line("  Name: " . ($route->getName() ?? 'N/A'));
            
            $handler = $route->getHandler();
            if (is_array($handler)) {
                $this->line("  Handler: " . implode('@', $handler));
            }
            
            if (!empty($route->getParameters())) {
                $this->line("  Parameters: " . json_encode($route->getParameters()));
            }
        } else {
            $this->error("✗ No route found for {$method} {$uri}");
        }
    }
}