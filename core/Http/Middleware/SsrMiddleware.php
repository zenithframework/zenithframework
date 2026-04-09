<?php

declare(strict_types=1);

namespace Zenith\Http\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Http\SsrEngine;
use Zenith\Http\IsrEngine;

class SsrMiddleware
{
    /**
     * Handle request with SSR caching
     * 
     * Usage in routes:
     * $router->get('/page', fn() => view('page'), ['ssr' => ['ttl' => 3600]]);
     */
    public function handle(Request $request, \Closure $next, array $options = []): mixed
    {
        $result = $next($request);

        // If it's already a Response, cache it
        if ($result instanceof Response) {
            $content = $result->getContent();
            $cacheKey = $this->generateCacheKey($request);
            $ttl = $options['ttl'] ?? 3600;

            // Cache the rendered HTML
            SsrEngine::cacheHtml($cacheKey, $content, $ttl);

            // Add SSR header
            $result = $result->withHeader('X-SSR', 'CACHED');
        }

        return $result;
    }

    protected function generateCacheKey(Request $request): string
    {
        $uri = $request->path();
        $queryParams = $request->query();
        
        return md5($uri . '_' . serialize($queryParams));
    }
}
