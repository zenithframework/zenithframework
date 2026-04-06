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
    protected array $middleware = [];

    public function __construct(Container $container, Router $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    public function handle(Request $request): Response
    {
        try {
            foreach ($this->middleware as $middlewareClass) {
                $middleware = $this->container->make($middlewareClass);
                $response = $middleware->handle($request, fn($req) => null);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            $route = $this->router->match($request);

            if ($route === null) {
                return new Response('Not Found', 404);
            }

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
        } catch (\Throwable $e) {
            return new Response('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }

    public function middleware(array $middleware): self
    {
        $this->middleware = $middleware;
        return $this;
    }
}
