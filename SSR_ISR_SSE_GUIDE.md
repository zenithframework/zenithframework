# SSR, ISR & SSE Features Guide

Ultra-fast rendering features built into Zenith Framework for maximum performance.

## 🚀 Overview

Zenith Framework now includes three powerful rendering strategies:

1. **SSR (Server-Side Rendering)** - Pre-render pages and cache HTML for instant subsequent loads
2. **ISR (Incremental Static Regeneration)** - Stale-while-revalidate pattern for perfect speed/freshness balance
3. **SSE (Server-Sent Events)** - Real-time push notifications from server to client

## ⚡ SSR (Server-Side Rendering)

### What is SSR?

SSR renders pages on the server and caches the generated HTML. The next request for the same page gets the cached HTML instantly, bypassing template rendering entirely.

**Best for:** Content-heavy pages that don't change frequently (homepages, blogs, documentation)

### Quick Start

#### Using the Helper Function

```php
// In your controller
public function home(): Response
{
    $html = ssr('pages.home', [
        'title' => 'My Page',
        'content' => 'Hello World',
    ], [
        'cache' => true,
        'ttl' => 3600, // Cache for 1 hour
    ]);

    return new Response($html);
}
```

#### Using the Engine Directly

```php
use Zen\Http\SsrEngine;

public function about(): Response
{
    $html = SsrEngine::render('pages.about', [
        'title' => 'About Us',
    ], [
        'cache' => true,
        'ttl' => 7200, // 2 hours
    ]);

    return new Response($html);
}
```

### Prerender Multiple Pages

```php
use Zen\Http\SsrEngine;

public function prerender(): Response
{
    $pages = [
        [
            'template' => 'pages.home',
            'data' => ['title' => 'Home'],
            'options' => ['cache' => true, 'ttl' => 3600],
        ],
        [
            'template' => 'pages.about',
            'data' => ['title' => 'About'],
            'options' => ['cache' => true, 'ttl' => 3600],
        ],
        [
            'template' => 'pages.blog',
            'data' => ['posts' => []],
            'options' => ['cache' => true, 'ttl' => 1800],
        ],
    ];

    $result = SsrEngine::prerender($pages);
    
    // Returns:
    // [
    //     'pages' => [...],
    //     'total_time' => '245.5ms',
    //     'cached' => 3,
    //     'failed' => 0,
    // ]

    return new Response(json_encode($result));
}
```

### Cache Management

```php
// Get cached HTML
$html = ssr_cache($cacheKey);

// Put HTML in cache
ssr_cache_put($cacheKey, $html, 3600);

// Remove from cache
ssr_forget($cacheKey);

// Clear all SSR cache
ssr_clear();

// Or using engine directly
SsrEngine::getCachedHtml($key);
SsrEngine::cacheHtml($key, $html, $ttl);
SsrEngine::invalidateCache($key);
SsrEngine::clearAllCache();
```

### Performance Headers

When `app.debug` is enabled, SSR adds a comment to the HTML showing render time:

```html
<!-- SSR Render Time: 12.5ms -->
```

## 🔄 ISR (Incremental Static Regeneration)

### What is ISR?

ISR uses a **stale-while-revalidate** pattern:
1. First request: Page is generated and cached (slow)
2. Subsequent requests: Served from cache (instant)
3. After TTL expires: Serves stale page **while regenerating in background**
4. Background regeneration updates the cache
5. Next request gets fresh version

**Best for:** Pages that need to stay fresh but should be fast (products, articles, user profiles)

### Quick Start

#### Using the Helper Function

```php
use Zen\Http\IsrEngine;

public function product($id): Response
{
    $product = Product::find($id);

    $isrResponse = IsrEngine::handle('pages.product', [
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
```

#### Using ISR Helper

```php
public function article($slug): Response
{
    $article = Article::where('slug', $slug)->first();

    $isrResponse = isr('pages.article', [
        'article' => $article,
    ], [
        'ttl' => 120, // 2 minutes
    ]);

    return new Response(
        $isrResponse->getContent(),
        200,
        $isrResponse->getHeaders()
    );
}
```

### ISR Response Headers

ISR automatically adds headers to help you understand cache status:

```
X-ISR: HIT          ← Served from cache (fresh)
X-ISR: STALE        ← Served stale, regenerating in background
X-ISR: MISS         ← Not in cache, generated fresh
X-Render-Time: 8.2ms
```

### On-Demand Revalidation

When data changes, force immediate regeneration:

```php
// In your controller when updating a product
public function update(Request $request, $id): Response
{
    // Update product in database
    $product->update($request->all());

    // Revalidate the product page immediately
    isr_revalidate('pages.product', [
        'product' => $product,
    ], ['ttl' => 60]);

    return redirect('/products/' . $id);
}
```

### Revalidation Callbacks

Register callbacks to run after page regeneration (e.g., clear related caches):

```php
// In config/ssr.php
'callbacks' => [
    'products/*' => [
        function($template, $data) {
            // Clear related caches
            ssr_forget('products_list');
            cache()->forget('featured_products');
        }
    ],
],

// Or programmatically
use Zen\Http\IsrEngine;

IsrEngine::onRevalidate('blog/*', function($template, $data) {
    // Update sitemap
    // Notify search engine
    // Clear tag caches
});
```

### Cache Management

```php
// Revalidate specific page
isr_revalidate('pages.product', ['product' => $product]);

// Remove from cache
isr_forget($cacheKey);

// Clear all ISR cache
isr_clear();

// Or using engine
IsrEngine::revalidate($template, $data, $options);
IsrEngine::invalidateCache($key);
IsrEngine::clearAllCache();
IsrEngine::getCacheStats();
IsrEngine::warmCache($pages);
```

## 📡 SSE (Server-Sent Events)

### What is SSE?

SSE allows the server to push real-time updates to the browser over a single HTTP connection. The client uses `EventSource` API to listen for events.

**Best for:** Live notifications, chat, progress indicators, real-time dashboards

### Built-in SSE Classes

Zenith Framework includes complete SSE support:
- `SseEvent` - Individual event
- `SseStream` - Event stream manager
- `StreamingResponse` - HTTP response for SSE

### Creating an SSE Endpoint

```php
use Zen\Http\SseEvent;
use Zen\Http\SseStream;
use Zen\Http\StreamingResponse;

#[\Zen\Routing\Attributes\Get('/sse/notifications')]
public function notifications(): StreamingResponse
{
    return StreamingResponse::fromDataProvider(
        dataProvider: function (?string $lastEventId): array {
            // Fetch new notifications
            $notifications = $this->getNewNotifications($lastEventId);

            return array_map(
                fn($notification) => new SseEvent(
                    data: [
                        'id' => $notification['id'],
                        'message' => $notification['message'],
                        'type' => $notification['type'],
                    ],
                    event: $notification['type'],
                    id: (string) $notification['id']
                ),
                $notifications
            );
        },
        pollInterval: 2,          // Check every 2 seconds
        heartbeatInterval: 15,    // Heartbeat every 15s to keep connection alive
        timeout: 600              // Max connection time: 10 minutes
    );
}
```

### Client-Side Code (JavaScript)

```javascript
// Connect to SSE endpoint
const eventSource = new EventSource('/sse/notifications');

// Listen for specific event types
eventSource.addEventListener('new-message', (event) => {
    const data = JSON.parse(event.data);
    console.log('New message:', data.message);
    
    // Update UI
    showNotification(data);
});

eventSource.addEventListener('notification', (event) => {
    const data = JSON.parse(event.data);
    console.log('Notification:', data);
});

// Handle connection open
eventSource.onopen = () => {
    console.log('SSE connection opened');
};

// Handle errors
eventSource.onerror = (error) => {
    console.error('SSE error:', error);
    // EventSource automatically reconnects
};
```

### SSE Event Options

```php
// Simple event with data
new SseEvent(
    data: ['count' => 42],
    event: 'update',
    id: 'event-123'
);

// Event with retry interval (client will reconnect after this time if disconnected)
new SseEvent(
    data: ['status' => 'processing'],
    event: 'progress',
    id: 'event-124',
    retry: 3000  // 3 seconds
);

// Comment (heartbeat to keep connection alive)
SseEvent::comment('heartbeat');
```

### StreamingResponse Options

```php
// From data provider (polling mode)
StreamingResponse::fromDataProvider(
    dataProvider: function (?string $lastEventId): array {
        // Return array of SseEvent objects
        return [new SseEvent(data: ['time' => time()])];
    },
    pollInterval: 1,           // Poll every 1 second
    heartbeatInterval: 15,     // Heartbeat every 15 seconds
    timeout: 300               // Close after 5 minutes
);

// From subscription (pubsub mode - for advanced use cases)
StreamingResponse::fromSubscription(
    subscription: $pubSubSubscription,
    heartbeatInterval: 15,
    timeout: 600
);
```

## 🛠️ CLI Commands

### Cache Management

```bash
# View cache statistics
php zen cache:ssr stats
php zen cache:ssr stats ssr
php zen cache:ssr stats isr

# Clear cache
php zen cache:ssr clear
php zen cache:ssr clear ssr
php zen cache:ssr clear isr

# Warm cache (pre-render pages)
php zen cache:ssr warm

# Revalidate specific page
php zen cache:ssr revalidate /blog/post-1
```

### Other Useful Commands

```bash
# Start development server
php zen serve

# Check code for errors
php zen lint

# Run tests
php zen test
```

## ⚙️ Configuration

All SSR/ISR/SSE settings are in `config/ssr.php`:

```php
return [
    'ssr' => [
        'enabled' => env('SSR_ENABLED', true),
        'cache_path' => env('SSR_CACHE_PATH', 'storage/ssr/'),
        'ttl' => env('SSR_TTL', 3600), // 1 hour
        
        // Pages to prerender on cache:warm
        'prerender' => [
            // [
            //     'template' => 'pages/home',
            //     'data' => [],
            //     'options' => ['cache' => true, 'ttl' => 3600],
            // ],
        ],
    ],

    'isr' => [
        'enabled' => env('ISR_ENABLED', true),
        'cache_path' => env('ISR_CACHE_PATH', 'storage/isr/'),
        'ttl' => env('ISR_TTL', 60), // 60 seconds
        'background_revalidation' => true,
        
        // Pages to warm
        'warm_pages' => [
            // [
            //     'template' => 'blog/index',
            //     'data' => [],
            //     'options' => ['ttl' => 120],
            // ],
        ],
        
        // Revalidation callbacks
        'callbacks' => [
            // 'blog/*' => [
            //     function($template, $data) {
            //         // Clear related caches
            //     }
            // ],
        ],
    ],

    'sse' => [
        'enabled' => true,
        'heartbeat_interval' => env('SSE_HEARTBEAT_INTERVAL', 15),
        'timeout' => env('SSE_TIMEOUT', 300),
        'poll_interval' => env('SSE_POLL_INTERVAL', 1000),
    ],
];
```

## 📊 When to Use Each

| Feature | Use Case | Speed | Freshness | Example |
|---------|----------|-------|-----------|---------|
| **SSR** | Static content | ⚡⚡⚡ | Low | Homepage, About, Docs |
| **ISR** | Semi-dynamic | ⚡⚡⚡ | High | Products, Articles, Profiles |
| **SSE** | Real-time | ⚡⚡ | Live | Notifications, Chat, Live Stats |

## 🎯 Example Routes

```php
// routes/Web.php

// SSR - Static homepage
$router->get('/', fn() => ssr('pages.home', [], ['ttl' => 7200]));

// ISR - Product pages (update every minute)
$router->get('/products/{id}', [ProductController::class, 'show']);

// SSE - Live notifications
$router->get('/sse/notifications', [NotificationController::class, 'stream']);
```

## 📝 Example Controllers

Check these files for complete examples:
- `app/Http/Controllers/SsrExampleController.php`
- `app/Http/Controllers/IsrExampleController.php`
- `app/Http/Controllers/SseController.php`

## 🧪 Testing the Features

### Test SSR

```bash
# First request (generates and caches)
curl http://localhost:8000/ssr

# Second request (instant from cache)
curl -i http://localhost:8000/ssr
# Look for: X-SSR: CACHED header
```

### Test ISR

```bash
# First request (generates page)
curl -i http://localhost:8000/isr/product/1
# Look for: X-ISR: MISS

# Second request (from cache)
curl -i http://localhost:8000/isr/product/1
# Look for: X-ISR: HIT

# After TTL expires (stale while regenerating)
curl -i http://localhost:8000/isr/product/1
# Look for: X-ISR: STALE
```

### Test SSE

```javascript
// In browser console
const es = new EventSource('/sse/time');
es.addEventListener('time-update', (e) => {
    console.log('Time:', JSON.parse(e.data).time);
});
```

## 🚀 Performance Tips

1. **Use SSR for pages that rarely change** - Homepage, about page, static content
2. **Use ISR for pages that change occasionally** - Products, blog posts, user profiles
3. **Use SSE for real-time features** - Notifications, chat, live updates
4. **Warm cache on deployment** - Run `php zen cache:ssr warm` after deploying
5. **Monitor cache hit rates** - Check `X-ISR` headers to verify caching is working
6. **Set appropriate TTLs** - Shorter for dynamic content, longer for static

## 📂 File Structure

```
zen/
├── core/Http/
│   ├── SsrEngine.php           # SSR rendering engine
│   ├── IsrEngine.php           # ISR engine with stale-while-revalidate
│   ├── IsrResponse.php         # ISR-specific response
│   ├── SseEvent.php            # SSE event object
│   ├── SseStream.php           # SSE stream manager
│   ├── StreamingResponse.php   # HTTP response for SSE
│   └── Middleware/
│       ├── SsrMiddleware.php   # SSR middleware
│       └── IsrMiddleware.php   # ISR middleware
├── config/
│   └── ssr.php                 # SSR/ISR/SSE configuration
├── storage/
│   ├── ssr/                    # SSR cache directory
│   └── isr/                    # ISR cache directory
└── app/
    ├── Providers/
    │   └── SsrServiceProvider.php
    └── Http/Controllers/
        ├── SsrExampleController.php
        ├── IsrExampleController.php
        └── SseController.php
```

## 🎓 Learn More

- Visit `/demo` in your browser to see all features in action
- Check example controllers for implementation patterns
- Run `php zen cache:ssr` to view cache statistics

---

Built with ❤️ for Zenith Framework - PHP 8.5+
