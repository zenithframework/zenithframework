<?php

declare(strict_types=1);

namespace Zenith\Routing\Attributes;

/**
 * Base route attribute
 */
abstract class RouteAttribute
{
    public function __construct(
        public string $uri,
        public array $middleware = [],
        public ?string $name = null
    ) {
    }

    abstract public function getMethod(): string;
}
