<?php

declare(strict_types=1);

namespace Zenith\Routing;

class RadixNode
{
    public string $segment;
    public bool $isVariable = false;
    public bool $isOptional = false;
    public ?string $variableName = null;
    public array $children = [];
    public ?Route $route = null;
    public array $middleware = [];

    public function __construct(string $segment)
    {
        $this->segment = $segment;
        $this->isVariable = str_starts_with($segment, '{');
        $this->isOptional = str_ends_with($segment, '?');
        
        if ($this->isVariable) {
            $this->variableName = trim($segment, '{}?');
        }
    }

    public function addChild(RadixNode $child): void
    {
        $key = $child->isVariable ? ':variable' : $child->segment;
        $this->children[$key] = $child;
    }

    public function getChild(string $segment): ?RadixNode
    {
        if (isset($this->children[$segment])) {
            return $this->children[$segment];
        }

        if (isset($this->children[':variable'])) {
            return $this->children[':variable'];
        }

        return null;
    }

    public function findMatchingChild(string $segment): ?RadixNode
    {
        if (isset($this->children[$segment])) {
            return $this->children[$segment];
        }

        if (isset($this->children[':variable'])) {
            return $this->children[':variable'];
        }

        return null;
    }
}

class RadixRouter
{
    protected ?RadixNode $root = null;
    protected array $namedRoutes = [];
    protected array $routes = [];
    protected ?string $groupPrefix = null;
    protected array $groupMiddleware = [];

    public function __construct()
    {
        $this->root = new RadixNode('');
    }

    public function get(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $this->addRoute('GET', $uri, $handler, $name);
    }

    public function post(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $this->addRoute('POST', $uri, $handler, $name);
    }

    public function put(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $this->addRoute('PUT', $uri, $handler, $name);
    }

    public function patch(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $this->addRoute('PATCH', $uri, $handler, $name);
    }

    public function delete(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $this->addRoute('DELETE', $uri, $handler, $name);
    }

    public function any(string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->addRoute($method, $uri, $handler, $name);
        }
    }

    public function addRoute(string $method, string $uri, array|string|\Closure $handler, ?string $name = null): void
    {
        $prefix = $this->groupPrefix ?? '';
        $fullUri = $prefix . '/' . ltrim($uri, '/');
        $fullUri = $fullUri === '/' ? '/' : rtrim($fullUri, '/');

        $segments = $this->parseUri($fullUri);
        $node = $this->findOrCreateNode($segments);
        
        $route = new Route($method, $fullUri, $handler);
        $route->setMiddleware($this->groupMiddleware);
        
        if ($name !== null) {
            $route->setName($name);
            $this->namedRoutes[$name] = $route;
        }

        $node->route = $route;
        $this->routes[] = $route;
    }

    public function group(string $prefix, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;
        
        $this->groupPrefix = ($this->groupPrefix ?? '') . '/' . ltrim($prefix, '/');
        $this->groupMiddleware = [];
        
        $callback($this);
        
        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function match(\Zenith\Http\Request $request): ?Route
    {
        $method = $request->method;
        $uri = $request->uri;
        
        $segments = $this->parseUri($uri);
        
        return $this->matchSegments($this->root, $segments, $method);
    }

    protected function matchSegments(RadixNode $node, array $segments, string $method, int $depth = 0): ?Route
    {
        if (!isset($segments[$depth])) {
            if ($node->route !== null && ($node->route->getMethod() === $method || $node->route->getMethod() === 'ANY')) {
                return $node->route;
            }
            return null;
        }

        $segment = $segments[$depth];
        $child = $node->findMatchingChild($segment);

        if ($child !== null) {
            $result = $this->matchSegments($child, $segments, $method, $depth + 1);
            
            if ($result !== null) {
                return $result;
            }
        }

        if ($node->route !== null && ($node->route->getMethod() === $method || $node->route->getMethod() === 'ANY')) {
            return $node->route;
        }

        return null;
    }

    protected function parseUri(string $uri): array
    {
        $uri = trim($uri, '/');
        
        if (empty($uri)) {
            return [''];
        }
        
        return explode('/', $uri);
    }

    protected function findOrCreateNode(array $segments): RadixNode
    {
        $node = $this->root;
        
        foreach ($segments as $segment) {
            $child = $node->getChild($segment);
            
            if ($child === null) {
                $child = new RadixNode($segment);
                $node->addChild($child);
            }
            
            $node = $child;
        }
        
        return $node;
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Route [{$name}] not found.");
        }

        $uri = $this->namedRoutes[$name]->getUri();

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

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function getStats(): array
    {
        $stats = ['nodes' => 0, 'depth' => 0];
        $this->collectStats($this->root, $stats);
        
        return [
            'total_routes' => count($this->routes),
            'named_routes' => count($this->namedRoutes),
            'total_nodes' => $stats['nodes'] ?? 0,
            'max_depth' => $stats['depth'] ?? 0,
        ];
    }

    protected function collectStats(RadixNode $node, array &$stats, int $depth = 0): void
    {
        $stats['nodes'] = ($stats['nodes'] ?? 0) + 1;
        $stats['depth'] = max($stats['depth'] ?? 0, $depth);

        foreach ($node->children as $child) {
            $this->collectStats($child, $stats, $depth + 1);
        }
    }
}