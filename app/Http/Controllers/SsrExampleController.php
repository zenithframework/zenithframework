<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Http\SsrEngine;

class SsrExampleController
{
    /**
     * Example: SSR cached page
     * This renders a page and caches the HTML output
     */
    public function home(): Response
    {
        // Using SSR helper with caching
        $html = ssr('pages.ssr-home', [
            'title' => 'SSR Home Page',
            'message' => 'This page is server-side rendered and cached',
            'timestamp' => now(),
        ], [
            'cache' => true,
            'ttl' => 3600, // Cache for 1 hour
        ]);

        return new Response($html);
    }

    /**
     * Example: Blog list with SSR
     */
    public function blog(): Response
    {
        // Simulate blog posts
        $posts = [
            ['id' => 1, 'title' => 'Getting Started with Zenith Framework', 'excerpt' => 'Learn the basics...'],
            ['id' => 2, 'title' => 'Advanced SSR Techniques', 'excerpt' => 'Master caching...'],
            ['id' => 3, 'title' => 'Real-time with SSE', 'excerpt' => 'Build live updates...'],
        ];

        $html = ssr('pages.ssr-blog', [
            'title' => 'Blog',
            'posts' => $posts,
        ], [
            'cache' => true,
            'ttl' => 1800, // Cache for 30 minutes
        ]);

        return new Response($html);
    }

    /**
     * Example: Prerender multiple pages
     */
    public function prerender(): Response
    {
        $pages = [
            [
                'template' => 'pages.ssr-home',
                'data' => ['title' => 'Home', 'message' => 'Welcome!', 'timestamp' => now()],
                'options' => ['cache' => true, 'ttl' => 3600],
            ],
            [
                'template' => 'pages.ssr-blog',
                'data' => ['title' => 'Blog', 'posts' => []],
                'options' => ['cache' => true, 'ttl' => 1800],
            ],
        ];

        $result = SsrEngine::prerender($pages);

        return new Response(
            json_encode($result, JSON_PRETTY_PRINT),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Example: SSR with dynamic data
     */
    public function dynamic(Request $request): Response
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        // This will cache each unique combination of parameters
        $html = ssr('pages.ssr-dynamic', [
            'title' => 'Dynamic Page',
            'page' => $page,
            'perPage' => $perPage,
            'items' => range(1, $perPage),
        ], [
            'cache' => true,
            'ttl' => 600, // Cache for 10 minutes
        ]);

        return new Response($html);
    }
}
