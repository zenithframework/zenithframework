<?php

declare(strict_types=1);

namespace Zen\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
