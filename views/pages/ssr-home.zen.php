<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'SSR Home') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .hero { text-align: center; padding: 4rem 0; }
        .hero h1 { font-size: 3.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        .hero p { font-size: 1.25rem; color: #94a3b8; margin-bottom: 2rem; }
        .badge { display: inline-block; background: #10b981; color: white; padding: 0.5rem 1.5rem; border-radius: 9999px; font-weight: 600; margin: 0.5rem; }
        .card { background: #1e293b; border-radius: 0.5rem; padding: 2rem; margin-top: 2rem; }
        .timestamp { color: #64748b; font-size: 0.875rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>🚀 SSR Home Page</h1>
            <p><?= e($message ?? 'Server-Side Rendering with Caching') ?></p>
            <div>
                <span class="badge">⚡ Super Fast</span>
                <span class="badge">🎯 Cached</span>
                <span class="badge">🔥 Production Ready</span>
            </div>
        </div>

        <div class="card">
            <h2>What is SSR?</h2>
            <p>Server-Side Rendering (SSR) renders pages on the server and caches the HTML. This provides instant load times for subsequent requests.</p>
            <div class="timestamp">⏰ Rendered at: <?= e($timestamp ?? 'N/A') ?></div>
        </div>

        <div class="card">
            <h2>Features</h2>
            <ul style="list-style: none; padding: 0;">
                <li>✅ Pre-rendered HTML cached on server</li>
                <li>✅ Configurable TTL for cache expiration</li>
                <li>✅ Automatic cache invalidation</li>
                <li>✅ Perfect for content-heavy pages</li>
                <li>✅ Reduces server load significantly</li>
            </ul>
        </div>
    </div>
</body>
</html>
