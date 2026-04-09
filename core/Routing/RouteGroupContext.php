<?php

declare(strict_types=1);

namespace Zenith\Routing;

class RouteGroupContext
{
    public function __construct(
        public readonly string $prefix
    ) {
    }
}