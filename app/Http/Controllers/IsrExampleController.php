<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Http\IsrEngine;

class IsrExampleController
{
    /**
     * Example: ISR page with stale-while-revalidate
     * First request is slow (generates page), subsequent requests are instant (cached)
     * After TTL expires, serves stale page while regenerating in background
     */
    public function product($id): Response
    {
        // Simulate fetching product from database
        $product = [
            'id' => $id,
            'name' => "Product #{$id}",
            'price' => rand(10, 100),
            'description' => 'This is a sample product description.',
            'stock' => rand(0, 100),
        ];

        // ISR will cache this page. After TTL, it regenerates in background
        $isrResponse = IsrEngine::handle('pages.isr-product', [
            'product' => $product,
        ], [
            'ttl' => 60, // Regenerate every 60 seconds
        ]);

        return new Response(
            $isrResponse->getContent(),
            $isrResponse->getStatusCode(),
            $isrResponse->getHeaders()
        );
    }

    /**
     * Example: News article with ISR
     */
    public function article($slug): Response
    {
        // Simulate fetching article
        $article = [
            'slug' => $slug,
            'title' => "Article: {$slug}",
            'content' => 'This is the article content...',
            'author' => 'John Doe',
            'publishedAt' => date('Y-m-d H:i:s'),
        ];

        $isrResponse = IsrEngine::handle('pages.isr-article', [
            'article' => $article,
        ], [
            'ttl' => 120, // Regenerate every 2 minutes
        ]);

        return new Response(
            $isrResponse->getContent(),
            $isrResponse->getStatusCode(),
            $isrResponse->getHeaders()
        );
    }

    /**
     * Example: Revalidate specific page on demand
     * Call this when data changes (e.g., product update)
     */
    public function revalidate(Request $request): Response
    {
        $template = $request->post('template');
        $data = $request->post('data', []);

        if (!$template) {
            return new Response(
                json_encode(['error' => 'Template is required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $success = IsrEngine::revalidate($template, $data, ['ttl' => 120]);

        return new Response(
            json_encode([
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Page revalidated' : 'Failed to revalidate',
            ]),
            $success ? 200 : 500,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Example: Warm ISR cache with frequently accessed pages
     */
    public function warmCache(): Response
    {
        $pages = [
            [
                'template' => 'pages.isr-product',
                'data' => ['product' => ['id' => 1, 'name' => 'Product 1', 'price' => 29.99]],
                'options' => ['ttl' => 60],
            ],
            [
                'template' => 'pages.isr-article',
                'data' => ['article' => ['slug' => 'getting-started', 'title' => 'Getting Started']],
                'options' => ['ttl' => 120],
            ],
        ];

        $result = IsrEngine::warmCache($pages);

        return new Response(
            json_encode($result, JSON_PRETTY_PRINT),
            200,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Example: ISR cache statistics
     */
    public function stats(): Response
    {
        $stats = IsrEngine::getCacheStats();

        return new Response(
            json_encode($stats, JSON_PRETTY_PRINT),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
