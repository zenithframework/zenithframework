<?php

declare(strict_types=1);

namespace Zen\Routing;

class RouteGroupContext
{
    public function __construct(
        public readonly string $prefix
    ) {
    }
}