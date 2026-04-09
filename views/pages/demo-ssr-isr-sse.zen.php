<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zenith Framework - SSR, ISR & SSE Demo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .hero { text-align: center; padding: 4rem 0; }
        .hero h1 { font-size: 3.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        .hero p { font-size: 1.25rem; color: #94a3b8; margin-bottom: 2rem; }
        .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin: 3rem 0; }
        .feature-card { background: #1e293b; border-radius: 1rem; padding: 2rem; transition: transform 0.2s, box-shadow 0.2s; }
        .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); }
        .feature-icon { font-size: 3rem; margin-bottom: 1rem; }
        .feature-title { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .feature-desc { color: #94a3b8; margin-bottom: 1rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; margin: 0.25rem; }
        .badge-ssr { background: #10b981; color: white; }
        .badge-isr { background: #f59e0b; color: white; }
        .badge-sse { background: #3b82f6; color: white; }
        .links { margin-top: 3rem; }
        .links h2 { margin-bottom: 1.5rem; text-align: center; }
        .link-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .link-item { background: #1e293b; padding: 1.5rem; border-radius: 0.5rem; text-decoration: none; color: #e2e8f0; transition: all 0.2s; display: block; }
        .link-item:hover { background: #334155; transform: translateX(4px); }
        .link-item h3 { margin-bottom: 0.5rem; color: #60a5fa; }
        .link-item p { color: #94a3b8; font-size: 0.875rem; }
        code { background: #0f172a; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>🚀 Zenith Framework</h1>
            <p>Super-fast SSR, ISR, and SSE features built right in!</p>
        </div>

        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h2 class="feature-title">SSR - Server-Side Rendering</h2>
                <p class="feature-desc">Pre-render pages on the server and cache HTML for instant subsequent loads</p>
                <span class="badge badge-ssr">✓ Cached</span>
                <span class="badge badge-ssr">⚡ Fast</span>
                <span class="badge badge-ssr">🎯 Perfect for static content</span>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h2 class="feature-title">ISR - Incremental Static Regeneration</h2>
                <p class="feature-desc">Stale-while-revalidate pattern: serve cached pages while regenerating in background</p>
                <span class="badge badge-isr">🔄 Auto-update</span>
                <span class="badge badge-isr">⚡ Instant</span>
                <span class="badge badge-isr">🔥 Always fresh</span>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📡</div>
                <h2 class="feature-title">SSE - Server-Sent Events</h2>
                <p class="feature-desc">Real-time push notifications from server to client with automatic reconnection</p>
                <span class="badge badge-sse">📡 Real-time</span>
                <span class="badge badge-sse">🔔 Push notifications</span>
                <span class="badge badge-sse">♻️ Auto-reconnect</span>
            </div>
        </div>

        <div class="links">
            <h2>🎯 Try the Examples</h2>
            <div class="link-grid">
                <a href="/ssr" class="link-item">
                    <h3>⚡ SSR Home</h3>
                    <p>Server-side rendered page with HTML caching</p>
                    <code>GET /ssr</code>
                </a>

                <a href="/ssr/blog" class="link-item">
                    <h3>📝 SSR Blog</h3>
                    <p>Blog list with 30-minute cache TTL</p>
                    <code>GET /ssr/blog</code>
                </a>

                <a href="/isr/product/1" class="link-item">
                    <h3>🔄 ISR Product</h3>
                    <p>Product page with stale-while-revalidate</p>
                    <code>GET /isr/product/1</code>
                </a>

                <a href="/isr/article/getting-started" class="link-item">
                    <h3>📰 ISR Article</h3>
                    <p>Article with background regeneration</p>
                    <code>GET /isr/article/getting-started</code>
                </a>

                <a href="/isr/stats" class="link-item">
                    <h3>📊 ISR Stats</h3>
                    <p>View ISR cache statistics</p>
                    <code>GET /isr/stats</code>
                </a>

                <a href="/sse/time" class="link-item">
                    <h3>⏰ SSE Time Stream</h3>
                    <p>Real-time time updates via Server-Sent Events</p>
                    <code>GET /sse/time</code>
                </a>
            </div>
        </div>

        <div style="margin-top: 4rem; padding: 2rem; background: #1e293b; border-radius: 1rem;">
            <h2 style="margin-bottom: 1rem;">🛠️ CLI Commands</h2>
            <div style="display: grid; gap: 1rem;">
                <div>
                    <code>php zen cache:ssr stats</code>
                    <p style="color: #94a3b8; margin-top: 0.5rem;">View SSR and ISR cache statistics</p>
                </div>
                <div>
                    <code>php zen cache:ssr clear</code>
                    <p style="color: #94a3b8; margin-top: 0.5rem;">Clear all SSR and ISR cache</p>
                </div>
                <div>
                    <code>php zen cache:ssr warm</code>
                    <p style="color: #94a3b8; margin-top: 0.5rem;">Pre-render and cache configured pages</p>
                </div>
                <div>
                    <code>php zen cache:ssr revalidate /path</code>
                    <p style="color: #94a3b8; margin-top: 0.5rem;">Force revalidation of specific page</p>
                </div>
            </div>
        </div>

        <div style="margin-top: 3rem; text-align: center; padding: 2rem; color: #64748b;">
            <p>Built with ❤️ using Zenith Framework - PHP 8.5+</p>
        </div>
    </div>
</body>
</html>
