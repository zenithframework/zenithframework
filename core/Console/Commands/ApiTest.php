<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Http\Request;

class ApiTest extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $uri = $arguments[0] ?? '/api/status';
        $method = $arguments[1] ?? 'GET';
        $data = $arguments[2] ?? null;

        $router = $container->make(\Zenith\Routing\Router::class);
        
        $body = $data ? json_decode($data, true) : [];
        
        $request = Request::create($method, $uri, [], $body);
        $request = Request::create($method, $uri, [], is_array($data) ? $data : []);

        $route = $router->match($request);

        if (!$route) {
            $this->error("✗ No route found for {$method} {$uri}");
            return;
        }

        $this->info("Testing: {$method} {$uri}");
        $this->line("");

        try {
            $handler = $route->getHandler();
            $parameters = $route->getParameters();
            
            if (is_array($handler) && count($handler) === 2) {
                [$controller, $methodName] = $handler;
                $controllerInstance = $container->make($controller);
                $response = $controllerInstance->{$methodName}($request, ...$parameters);
            } elseif (is_callable($handler)) {
                $response = $handler($request, ...$parameters);
            } else {
                $this->error("Invalid handler");
                return;
            }

            $status = $response->getStatusCode();
            $content = $response->getContent();
            
            $this->line("Status: {$status}");
            $this->line("Response:");
            
            if (is_array($content) || is_object($content)) {
                $this->line(json_encode($content, JSON_PRETTY_PRINT));
            } else {
                $this->line($content);
            }
            
            if ($status >= 200 && $status < 300) {
                $this->info("\n✓ Test passed");
            } else {
                $this->error("\n✗ Test failed");
            }
        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}