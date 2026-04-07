<?php

declare(strict_types=1);

namespace Zen\Routing;

abstract class RouteGroup
{
    protected Router $router;
    protected string $prefix;
    protected array $middlewareStack = [];
    protected ?string $controllerPrefix = null;

    public function __construct(string $prefix) {
        $this->prefix = $prefix;
    }

    abstract public function define(): void;

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function load(Router $router): void
    {
        $this->router = $router;
        $this->define();
    }

    public function middleware(array|string $middleware): self
    {
        $middleware = is_string($middleware) ? [$middleware] : $middleware;
        $this->middlewareStack = array_merge($this->middlewareStack, $middleware);
        return $this;
    }

    public function controller(string $controller): self
    {
        $this->controllerPrefix = $controller;
        return $this;
    }

    public function get(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('GET', $uri, $handler, $name);
    }

    public function post(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('DELETE', $uri, $handler, $name);
    }

    public function options(string $uri, string|array $handler, ?string $name = null): Route
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $name);
    }

    protected function addRoute(string $method, string $uri, string|array $handler, ?string $name = null): Route
    {
        $fullPrefix = $this->prefix;
        
        $fullUri = rtrim($fullPrefix, '/') . '/' . ltrim($uri, '/');
        $fullUri = $fullUri === '/' ? '/' : rtrim($fullUri, '/');

        if (is_string($handler) && strpos($handler, '@') !== false) {
            $handler = $this->parseStringHandler($handler);
        }

        if (is_array($handler) && $this->controllerPrefix !== null) {
            $handler[0] = $this->controllerPrefix . '\\' . $handler[0];
        }

        $route = new Route($method, $fullUri, $handler);

        if (!empty($this->middlewareStack)) {
            $route->setMiddleware($this->middlewareStack);
        }

        if ($name !== null) {
            $route->setName($name);
        }

        $this->router->getRoutes()[] = $route;

        return $route;
    }

    protected function parseStringHandler(string $handler): array
    {
        if (strpos($handler, '@') === false) {
            return [$handler, '__invoke'];
        }

        [$controller, $method] = explode('@', $handler, 2);
        return [$controller, $method];
    }

    public function resource(string $name, string $controller): void
    {
        $controllerPrefix = $this->controllerPrefix ? $this->controllerPrefix . '\\' . $controller : $controller;
        
        $this->get($name, [$controllerPrefix, 'index']);
        $this->get($name . '/create', [$controllerPrefix, 'create']);
        $this->post($name, [$controllerPrefix, 'store']);
        $this->get($name . '/{id}', [$controllerPrefix, 'show']);
        $this->get($name . '/{id}/edit', [$controllerPrefix, 'edit']);
        $this->put($name . '/{id}', [$controllerPrefix, 'update']);
        $this->delete($name . '/{id}', [$controllerPrefix, 'destroy']);
    }

    public function apiResource(string $name, string $controller): void
    {
        $controllerPrefix = $this->controllerPrefix ? $this->controllerPrefix . '\\' . $controller : $controller;
        
        $this->get($name, [$controllerPrefix, 'index']);
        $this->post($name, [$controllerPrefix, 'store']);
        $this->get($name . '/{id}', [$controllerPrefix, 'show']);
        $this->put($name . '/{id}', [$controllerPrefix, 'update']);
        $this->delete($name . '/{id}', [$controllerPrefix, 'destroy']);
    }
}
