<?php

declare(strict_types=1);

namespace Zenith\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Middleware
{
    public function __construct(
        public array $middleware = []
    ) {
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Prefix
{
    public function __construct(
        public string $prefix
    ) {
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Name
{
    public function __construct(
        public string $name
    ) {
    }
}
