<?php

declare(strict_types=1);

namespace Zenith\Http\Middleware;

use Zenith\Container;
use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Diagnostics\DebugPanel;

class DebugMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!DebugPanel::isEnabled()) {
            return $next($request);
        }

        // Start debugging
        DebugPanel::start();

        // Record request data
        DebugPanel::setRequest([
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers(),
            'query' => $request->query(),
            'body' => $request->body(),
        ]);

        // Capture session data
        if (session_status() === PHP_SESSION_ACTIVE) {
            DebugPanel::setSession($_SESSION);
        }

        // Process request
        $response = $next($request);

        // Record response data
        DebugPanel::setResponse([
            'status' => $response->statusCode(),
            'headers' => $response->headers(),
            'content_length' => strlen($response->content()),
        ]);

        // Stop debugging
        DebugPanel::stop();

        // Inject debug toolbar into HTML responses
        if ($this->isHtmlResponse($response)) {
            $content = $response->content();
            $content = DebugPanel::inject($content);
            
            return new Response(
                $content,
                $response->statusCode(),
                $response->headers()
            );
        }

        return $response;
    }

    protected function isHtmlResponse(Response $response): bool
    {
        $headers = $response->headers();
        $contentType = $headers['Content-Type'] ?? '';
        
        return strpos($contentType, 'text/html') !== false;
    }
}
