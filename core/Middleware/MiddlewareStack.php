<?php

declare(strict_types=1);

namespace Zenith\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Container;

class MiddlewareStack
{
    protected array $middlewares = [];
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function add(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function middleware(array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function send(Request $request): Response
    {
        return $this->dispatch($request, $this->middlewares);
    }

    protected function dispatch(Request $request, array $middlewares): Response
    {
        if (empty($middlewares)) {
            return $this->terminate($request);
        }

        $middleware = array_shift($middlewares);
        $instance = $this->resolve($middleware);

        $next = function (Request $req) use ($middlewares) {
            return $this->dispatch($req, $middlewares);
        };

        return $instance->handle($request, $next);
    }

    protected function resolve(string $middleware): MiddlewareInterface
    {
        $aliases = [
            'auth' => \App\Http\Middleware\Auth::class,
            'guest' => \App\Http\Middleware\Guest::class,
            'csrf' => \App\Http\Middleware\Csrf::class,
            'throttle' => \Zenith\Middleware\ThrottleMiddleware::class,
            'rate_limit' => \Zenith\Middleware\ThrottleMiddleware::class,
        ];

        $class = $aliases[$middleware] ?? $middleware;

        if ($this->container->has($class)) {
            return $this->container->make($class);
        }

        return new $class();
    }

    protected function terminate(Request $request): Response
    {
        return new Response('Not Found', 404);
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
