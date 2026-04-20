<?php

declare(strict_types=1);

namespace Zenith\Routing;

use Closure;
use Zenith\Routing\Router;

/**
 * Fluent route group builder (Laravel 13+ style).
 */
class RouteGroupBuilder
{
    protected Router $router;
    protected string $prefix = '';
    protected array $middleware = [];
    protected string $name = '';
    protected string $domain = '';
    protected array $bindings = [];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Set the prefix for the group.
     *
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set middleware for the group.
     *
     * @param array|string $middleware
     * @return $this
     */
    public function middleware(array|string $middleware): self
    {
        $this->middleware = is_array($middleware) ? $middleware : func_get_args();
        return $this;
    }

    /**
     * Set middleware group for the group.
     *
     * @param string $middlewareGroup
     * @return $this
     */
    public function middlewareGroup(string $middlewareGroup): self
    {
        $this->middleware = $this->router->getMiddlewareGroup($middlewareGroup);
        return $this;
    }

    /**
     * Set the name prefix for the group.
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the domain for the group.
     *
     * @param string $domain
     * @return $this
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Set route model bindings for the group.
     *
     * @param array $bindings
     * @return $this
     */
    public function whereModel(array $bindings): self
    {
        $this->bindings = $bindings;
        return $this;
    }

    /**
     * Register a GET route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function get(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('GET', $uri, $handler, $name);
    }

    /**
     * Register a POST route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function post(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('POST', $uri, $handler, $name);
    }

    /**
     * Register a PUT route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function put(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('PUT', $uri, $handler, $name);
    }

    /**
     * Register a PATCH route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function patch(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('PATCH', $uri, $handler, $name);
    }

    /**
     * Register a DELETE route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function delete(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('DELETE', $uri, $handler, $name);
    }

    /**
     * Register an OPTIONS route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function options(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        return $this->addRoute('OPTIONS', $uri, $handler, $name);
    }

    /**
     * Register an ANY route.
     *
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    public function any(string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        $firstRoute = null;
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $firstRoute = $this->addRoute($method, $uri, $handler, $name);
        }
        return $firstRoute;
    }

    /**
     * Register a resource controller.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $this->router->resourceV2($this->getFullUri($name), $controller, $options);
    }

    /**
     * Register an API resource controller.
     *
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return void
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $this->router->apiResourceV2($this->getFullUri($name), $controller, $options);
    }

    /**
     * Add a route to the router.
     *
     * @param string $method
     * @param string $uri
     * @param Closure|array|string $handler
     * @param string|null $name
     * @return Route
     */
    protected function addRoute(string $method, string $uri, Closure|array|string $handler, ?string $name = null): Route
    {
        $fullUri = $this->getFullUri($uri);
        $fullName = $this->getFullName($name);

        $route = $this->router->addRoute($method, $fullUri, $handler, $fullName);

        if (!empty($this->middleware)) {
            $route->middleware($this->middleware);
        }

        if ($this->domain) {
            $route->domain($this->domain);
        }

        // Apply model bindings
        foreach ($this->bindings as $param => $binding) {
            if (is_array($binding)) {
                $route->whereModel($param, $binding['class'], $binding['column'] ?? null);
            } else {
                $route->whereModel($param, $binding);
            }
        }

        return $route;
    }

    /**
     * Get the full URI with prefix.
     *
     * @param string $uri
     * @return string
     */
    protected function getFullUri(string $uri): string
    {
        return rtrim($this->prefix, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Get the full route name.
     *
     * @param string|null $name
     * @return string|null
     */
    protected function getFullName(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return $this->name ? $this->name . '.' . $name : $name;
    }

    /**
     * Get the router instance.
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
}
