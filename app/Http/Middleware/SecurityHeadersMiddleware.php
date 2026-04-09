<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zen\Http\Request;
use Zen\Http\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');
        
        // Enable XSS protection
        $response->header('X-XSS-Protection', '1; mode=block');
        
        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');
        
        // Referrer Policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        $response->header('Content-Security-Policy', $this->getCSP());
        
        // Strict Transport Security (HTTPS only)
        if ($request->isSecure()) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        // Permissions Policy
        $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        // Remove server header
        $response->header('X-Powered-By', '');

        return $response;
    }

    protected function getCSP(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https: blob:",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "connect-src 'self' https://api.stripe.com https://api.paypal.com",
            "frame-src https://js.stripe.com https://www.paypal.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
