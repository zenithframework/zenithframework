<?php

declare(strict_types=1);

namespace Zenith\Http\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Http\IsrEngine;
use Zenith\Http\IsrResponse;

class IsrMiddleware
{
    /**
     * Handle request with ISR (stale-while-revalidate)
     * 
     * Usage in routes:
     * $router->get('/page', fn() => view('page'), ['isr' => ['ttl' => 60]]);
     */
    public function handle(Request $request, \Closure $next, array $options = []): mixed
    {
        $template = $options['template'] ?? null;
        
        if (!$template) {
            // If no template specified, just pass through
            return $next($request);
        }

        $data = $options['data'] ?? [];
        $ttl = $options['ttl'] ?? 60;

        // Handle ISR request
        $isrResponse = IsrEngine::handle($template, $data, [
            'ttl' => $ttl,
            'headers' => $options['headers'] ?? [],
            'status' => $options['status'] ?? 200,
        ]);

        // Convert to standard Response
        return new Response(
            $isrResponse->getContent(),
            $isrResponse->getStatusCode(),
            $isrResponse->getHeaders()
        );
    }
}
