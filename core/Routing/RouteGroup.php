<?php

declare(strict_types=1);

namespace Zen\Routing;

class RouteGroup
{
    protected Router $router;

    public function __construct(
        protected readonly string $prefix
    ) {
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function load(string $file): void
    {
        $router = app(Router::class);
        $this->router = $router;

        $register = function (Router $router) use ($file) {
            require $file;
        };

        $router->group($this->prefix, $register);
    }
}
