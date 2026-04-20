<?php

declare(strict_types=1);

namespace Zenith\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
