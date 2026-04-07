<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class RouteCache extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $router = $container->make(\Zen\Routing\Router::class);
        $routes = $router->getRoutes();

        $cacheDir = dirname(__DIR__, 3) . '/boot/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/routes.php';
        $data = "<?php\n\nreturn [\n";
        
        foreach ($routes as $route) {
            $method = $route->getMethod();
            $uri = $route->getUri();
            $name = $route->getName();
            $handler = is_array($route->getHandler()) 
                ? implode('@', $route->getHandler()) 
                : 'Closure';
            
            $data .= "    '{$method} {$uri}' => [\n";
            $data .= "        'handler' => '{$handler}',\n";
            $data .= "        'name' => " . ($name ? "'{$name}'" : "null") . ",\n";
            $data .= "    ],\n";
        }
        
        $data .= "];\n";
        
        file_put_contents($cacheFile, $data);
        
        $this->info("Route cache compiled: {$cacheFile}");
        $this->info("Cached " . count($routes) . " routes.");
    }
}