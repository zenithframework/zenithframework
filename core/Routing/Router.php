<?php

declare(strict_types=1);

namespace Zen\Routing;

use Closure;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected ?RouteGroupContext $currentGroup = null;
    protected array $middlewareStack = [];
    protected ?string $controllerPrefix = null;
    protected ?string $namePrefix = null;
    protected ?string $currentPrefix = null;

    public function getCurrentGroup(): ?RouteGroupContext
    {
        return $this->currentGroup;
    }

    public function setCurrentGroup(?RouteGroupContext $group): void
    {
        $this->currentGroup = $group;
    }

    public function get(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('GET', $uri, $handler, $name);
    }

    public function post(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('DELETE', $uri, $handler, $name);
    }

    public function options(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $name);
    }

    public function any(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        $firstRoute = null;
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $firstRoute = $this->addRoute($method, $uri, $handler, $name);
        }
        return $firstRoute;
    }

    public function group(string $prefix, Closure|array|string $callback): self
    {
        $previousGroup = $this->currentGroup;
        $previousPrefix = $this->currentPrefix;
        
        $this->currentPrefix = ($this->currentPrefix ?? '') . '/' . ltrim($prefix, '/');
        $this->currentGroup = new RouteGroupContext($this->currentPrefix);

        if (is_string($callback) || (is_array($callback) && is_string($callback[0] ?? ''))) {
            $className = is_string($callback) ? $callback : $callback[0];
            $this->loadRouteGroupClass($this->currentPrefix, $className);
        } elseif ($callback instanceof Closure) {
            $callback($this);
        }

        $this->currentGroup = $previousGroup;
        $this->currentPrefix = $previousPrefix;

        return $this;
    }

    public function loadRouteGroupClass(string $prefix, string $groupClass): void
    {
        if (!class_exists($groupClass)) {
            throw new \RuntimeException("RouteGroup class '{$groupClass}' not found.");
        }

        $reflection = new \ReflectionClass($groupClass);
        
        if (!$reflection->isSubclassOf(RouteGroup::class)) {
            throw new \RuntimeException("Class '{$groupClass}' must extend RouteGroup.");
        }

        $instance = $reflection->newInstance($prefix);
        $instance->load($this);
    }

    public function prefix(string $prefix): self
    {
        $this->currentPrefix = $prefix;
        $this->currentGroup = new RouteGroupContext($prefix);
        return $this;
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

    public function name(string $name): self
    {
        $this->namePrefix = $name;
        
        $route = end($this->routes);
        if ($route) {
            $fullName = $this->namePrefix ? $this->namePrefix . '.' . $name : $name;
            $route->setName($fullName);
            $this->namedRoutes[$fullName] = $route;
        }
        
        $this->namePrefix = null;
        return $this;
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

    public function match(\Zen\Http\Request $request): ?Route
    {
        $method = $request->method;
        $uri = $request->uri;

        foreach ($this->routes as $route) {
            if ($route->getMethod() !== $method && $route->getMethod() !== 'ANY') {
                continue;
            }

            $params = $this->matchUri($route->getUri(), $uri);

            if ($params !== null) {
                $route->setParameters($params);
                return $route;
            }
        }

        return null;
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not found.");
        }

        $route = $this->namedRoutes[$name];
        $uri = $route->getUri();

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        $uri = preg_replace('/\{[^}]+\?\}/', '', $uri);

        return $uri;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getRouteByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function getRouteByUri(string $uri, string $method): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->getUri() === $uri && ($route->getMethod() === $method || $route->getMethod() === 'ANY')) {
                return $route;
            }
        }
        return null;
    }

    public function addRoute(string $method, string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        if (is_string($handler)) {
            $handler = $this->parseStringHandler($handler);
        }

        if (is_array($handler) && $this->controllerPrefix !== null) {
            $handler[0] = $this->controllerPrefix . '\\' . $handler[0];
        }

        $prefix = $this->currentGroup?->prefix ?? '';
        $uri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        $route = new Route($method, $uri, $handler);

        if (!empty($this->middlewareStack)) {
            $route->setMiddleware($this->middlewareStack);
        }

        if ($name !== null) {
            $fullName = $this->namePrefix ? $this->namePrefix . '.' . $name : $name;
            $route->setName($fullName);
            $this->namedRoutes[$fullName] = $route;
        }

        $this->routes[] = $route;

        $this->middlewareStack = [];

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

    protected function matchUri(string $pattern, string $uri): ?array
    {
        $pattern = preg_replace('/\{(\w+)\?\}/', '(?P<$1>[^/]*)?', $pattern);
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
