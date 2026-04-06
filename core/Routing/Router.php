<?php

declare(strict_types=1);

namespace Zen\Routing;

use Closure;
use Zen\Http\Request;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected ?RouteGroup $currentGroup = null;

    public function get(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('GET', $uri, $handler, $name);
    }

    public function post(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        $this->addRoute('DELETE', $uri, $handler, $name);
    }

    public function any(string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $handler, $name);
        }
    }

    public function group(string $prefix, Closure $callback): void
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = new RouteGroup($prefix);
        $callback($this);
        $this->currentGroup = $previousGroup;
    }

    public function match(Request $request): ?Route
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

    protected function addRoute(string $method, string $uri, Closure|array|string $handler, ?string $name = null): void
    {
        if (is_string($handler)) {
            $handler = $this->parseStringHandler($handler);
        }

        $prefix = $this->currentGroup?->getPrefix() ?? '';
        $uri = rtrim($prefix, '/') . '/' . ltrim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        $route = new Route($method, $uri, $handler);

        if ($name !== null) {
            $route->setName($name);
            $this->namedRoutes[$name] = $route;
        }

        $this->routes[] = $route;
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
