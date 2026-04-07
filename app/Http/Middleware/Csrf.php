<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Middleware\MiddlewareInterface;

class Csrf implements MiddlewareInterface
{
    protected array $except = [];

    public function handle(Request $request, callable $next): Response
    {
        if ($this->isReadingRequest($request)) {
            return $next($request);
        }

        if (in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->header('X-CSRF-TOKEN') ?? $request->input('_token');
            $sessionToken = session()->get('_token');

            if (!$token || $token !== $sessionToken) {
                return new Response(json_encode(['error' => 'CSRF token mismatch']), 419, ['Content-Type' => 'application/json']);
            }
        }

        return $next($request);
    }

    protected function isReadingRequest(Request $request): bool
    {
        return in_array($request->method, ['GET', 'HEAD', 'OPTIONS']);
    }

    public function except(array $routes): self
    {
        $this->except = $routes;
        return $this;
    }
}
