<?php

declare(strict_types=1);

namespace Zen\Boot;

use Zen\Container;
use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Routing\Router;

class Engine
{
    protected Container $container;
    protected Router $router;
    protected array $globalMiddleware = [];

    public function __construct(Container $container, Router $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            $route = $this->router->match($request);

            if ($route === null) {
                return new Response('Not Found', 404);
            }

            $routeMiddleware = $route->getMiddleware();
            $allMiddleware = array_merge($this->globalMiddleware, $routeMiddleware);
            
            $pipeline = $this->buildPipeline($allMiddleware, $request, $route);
            
            return $pipeline;

        } catch (\Throwable $e) {
            return new Response('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }

    protected function buildPipeline(array $middleware, Request $request, $route): Response
    {
        if (empty($middleware)) {
            return $this->dispatchRoute($request, $route);
        }

        $middlewareClass = array_shift($middleware);
        
        $middlewareInstance = $this->resolveMiddleware($middlewareClass);
        
        $next = function (Request $req) use ($middleware, $route) {
            return $this->buildPipeline($middleware, $req, $route);
        };
        
        return $middlewareInstance->handle($request, $next);
    }

    protected function resolveMiddleware(string $middlewareClass): object
    {
        $aliases = [
            'auth' => \App\Http\Middleware\Auth::class,
            'guest' => \App\Http\Middleware\Guest::class,
            'csrf' => \App\Http\Middleware\Csrf::class,
        ];

        $class = $aliases[$middlewareClass] ?? $middlewareClass;
        
        return $this->container->make($class);
    }

    protected function dispatchRoute(Request $request, $route): Response
    {
        $handler = $route->getHandler();
        $parameters = $route->getParameters();

        if (is_array($handler) && count($handler) === 2) {
            [$controller, $method] = $handler;
            $controllerInstance = $this->container->make($controller);
            $response = $controllerInstance->{$method}($request, ...$parameters);
        } elseif (is_callable($handler)) {
            $response = $handler($request, ...$parameters);
        } else {
            return new Response('Internal Server Error', 500);
        }

        if (!$response instanceof Response) {
            $response = new Response((string) $response);
        }

        return $response;
    }

    public function middleware(array $middleware): self
    {
        $this->globalMiddleware = $middleware;
        return $this;
    }

    public function addMiddleware(string $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }
}
