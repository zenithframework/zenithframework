<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Zen\Http\Request;
use Zen\Http\Response;

class Csrf
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $token = $request->input('_token');
        $sessionToken = session_token();

        if ($token === null) {
            $token = $request->header('X-CSRF-TOKEN');
        }

        if ($token === null || $token !== $sessionToken) {
            return new Response('CSRF token mismatch', 419);
        }

        return $next($request);
    }
}
