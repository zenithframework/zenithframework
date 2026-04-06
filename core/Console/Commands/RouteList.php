<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Boot\RouteLoader;

class RouteList extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $compact = in_array('--compact', $arguments);
        $json = in_array('--json', $arguments);
        
        $router = $container->make(\Zen\Routing\Router::class);
        $routes = $router->getRoutes();
        
        if (empty($routes)) {
            $this->warn('No routes registered.');
            return;
        }
        
        $routeList = [];
        
        foreach ($routes as $route) {
            $routeList[] = [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'handler' => $this->formatHandler($route->getHandler()),
            ];
        }
        
        if ($json) {
            echo json_encode($routeList, JSON_PRETTY_PRINT) . "\n";
            return;
        }
        
        if ($compact) {
            foreach ($routeList as $r) {
                $name = $r['name'] ?? '-';
                $this->line("{$r['method']}\t{$r['uri']}\t{$name}");
            }
            return;
        }
        
        $this->info('Registered Routes:');
        $this->line(str_repeat('-', 80));
        
        foreach ($routeList as $r) {
            $method = str_pad($r['method'], 8);
            $uri = str_pad($r['uri'], 30);
            $name = $r['name'] ?? '(unnamed)';
            
            $this->line("{$method} {$uri} {$name}");
        }
        
        $this->line(str_repeat('-', 80));
        $this->info('Total routes: ' . count($routeList));
    }
    
    protected function formatHandler($handler): string
    {
        if (is_array($handler)) {
            return implode('@', $handler);
        }
        
        if (is_callable($handler)) {
            return 'Closure';
        }
        
        return (string) $handler;
    }
}
