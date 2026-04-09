<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SSR (Server-Side Rendering) Configuration
    |--------------------------------------------------------------------------
    |
    | SSR allows you to pre-render pages on the server and cache the HTML
    | for faster subsequent requests. This is perfect for content-heavy
    | pages that don't change frequently.
    |
    */

    'ssr' => [
        'enabled' => env('SSR_ENABLED', true),
        'cache_path' => env('SSR_CACHE_PATH', 'storage/ssr/'),
        
        // Default TTL for SSR cached pages (in seconds)
        'ttl' => env('SSR_TTL', 3600),

        // Pages to prerender on cache:warm command
        'prerender' => [
            // [
            //     'template' => 'pages/home',
            //     'data' => [],
            //     'options' => ['cache' => true, 'ttl' => 3600],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ISR (Incremental Static Regeneration) Configuration
    |--------------------------------------------------------------------------
    |
    | ISR combines the speed of static generation with the freshness of
    | server-side rendering. It serves stale cached pages while
    | regenerating them in the background.
    |
    */

    'isr' => [
        'enabled' => env('ISR_ENABLED', true),
        'cache_path' => env('ISR_CACHE_PATH', 'storage/isr/'),
        
        // Default TTL for ISR pages (in seconds)
        // Shorter than SSR to allow more frequent updates
        'ttl' => env('ISR_TTL', 60),

        // Enable background revalidation
        'background_revalidation' => true,

        // Pages to warm on cache:warm command
        'warm_pages' => [
            // [
            //     'template' => 'blog/index',
            //     'data' => [],
            //     'options' => ['ttl' => 120],
            // ],
        ],

        // Revalidation callbacks (triggered after page regeneration)
        'callbacks' => [
            // 'blog/*' => [
            //     function($template, $data) {
            //         // Clear related caches, notify services, etc.
            //     }
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SSE (Server-Sent Events) Configuration
    |--------------------------------------------------------------------------
    |
    | SSE allows real-time push notifications from server to client.
    | The framework already has full SSE support via StreamingResponse.
    |
    */

    'sse' => [
        'enabled' => true,
        
        // Heartbeat interval in seconds (keep connection alive)
        'heartbeat_interval' => env('SSE_HEARTBEAT_INTERVAL', 15),

        // Default timeout for SSE connections (in seconds)
        'timeout' => env('SSE_TIMEOUT', 300),

        // Poll interval for polling mode (in milliseconds)
        'poll_interval' => env('SSE_POLL_INTERVAL', 1000),
    ],

];
