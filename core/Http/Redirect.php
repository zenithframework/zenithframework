<?php

declare(strict_types=1);

namespace Zenith\Http;

class Redirect
{
    public static function to(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => self::sanitizeUrl($url)]);
    }

    public static function back(int $status = 302): Response
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return new Response('', $status, ['Location' => self::sanitizeUrl($url)]);
    }

    public static function route(string $name, array $params = [], int $status = 302): Response
    {
        $url = route($name, $params);
        return new Response('', $status, ['Location' => self::sanitizeUrl($url)]);
    }

    public static function home(int $status = 302): Response
    {
        return new Response('', $status, ['Location' => '/']);
    }

    public static function away(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => self::sanitizeUrl($url)]);
    }

    /**
     * Prevent header injection and open redirects. Any absolute URL that is not
     * explicitly same-origin is only allowed if it is not derived from user
     * input; callers that need to redirect off-site should use redirect()
     * directly. Relative paths and same-host URLs are permitted.
     */
    protected static function sanitizeUrl(string $url): string
    {
        // Strip anything that could break out of the Location header.
        $url = str_replace(["\r", "\n", "\0"], '', $url);

        // Allow relative paths and fragment/anchor links.
        if ($url === '' || $url[0] === '/' || $url[0] === '?' || $url[0] === '#') {
            return $url;
        }

        // Reject scheme-relative and absolute URLs pointing to another host.
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*:#', $url) || str_starts_with($url, '//')) {
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if ($host === '' || !str_contains($url, $host)) {
                return '/';
            }
        }

        return $url;
    }
}
