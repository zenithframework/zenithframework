<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Middleware\MiddlewareInterface;

class Auth implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!auth()->check()) {
            return redirect('/auth/login');
        }
        
        return $next($request);
    }
}
