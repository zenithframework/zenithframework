<?php

declare(strict_types=1);

namespace Zen\Routing;

use Closure;

class Route
{
    protected array $parameters = [];
    protected ?string $name = null;
    protected array $middleware = [];

    public function __construct(
        protected readonly string $method,
        protected readonly string $uri,
        protected readonly Closure|array|string $handler
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getHandler(): Closure|array|string
    {
        return $this->handler;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function setMiddleware(array $middleware): void
    {
        $this->middleware = array_merge($this->middleware, $middleware);
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }
}
