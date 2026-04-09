<?php

declare(strict_types=1);

namespace Zenith\Routing\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Get extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'GET';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Post extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'POST';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Put extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'PUT';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Patch extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'PATCH';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Delete extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'DELETE';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Options extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'OPTIONS';
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Any extends RouteAttribute
{
    public function getMethod(): string
    {
        return 'ANY';
    }
}
