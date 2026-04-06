<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Zen\Http\Request;
use Zen\Http\Response;

class Auth
{
    public function handle(Request $request, Closure $next): ?Response
    {
        return $next($request);
    }
}