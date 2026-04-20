<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($article['title'] ?? 'Article') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .article-header { margin-bottom: 2rem; }
        .article-title { font-size: 2.5rem; color: #60a5fa; margin-bottom: 1rem; }
        .article-meta { display: flex; gap: 1.5rem; color: #64748b; font-size: 0.875rem; }
        .meta-item { display: flex; align-items: center; gap: 0.5rem; }
        .article-content { background: #1e293b; padding: 2rem; border-radius: 0.5rem; margin-bottom: 2rem; }
        .article-content p { color: #94a3b8; line-height: 1.8; margin-bottom: 1rem; }
        .isr-notice { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 1.5rem; border-radius: 0.5rem; text-align: center; }
        .isr-notice h3 { margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="article-header">
            <h1 class="article-title"><?= e($article['title']) ?></h1>
            <div class="article-meta">
                <div class="meta-item">
                    <span>👤</span>
                    <span><?= e($article['author']) ?></span>
                </div>
                <div class="meta-item">
                    <span>📅</span>
                    <span><?= e($article['publishedAt']) ?></span>
                </div>
                <div class="meta-item">
                    <span>🔖</span>
                    <span><?= e($article['slug']) ?></span>
                </div>
            </div>
        </div>

        <div class="article-content">
            <p><?= e($article['content']) ?></p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
        </div>

        <div class="isr-notice">
            <h3>🔄 ISR Enabled</h3>
            <p>This article is cached and regenerated every 2 minutes using Incremental Static Regeneration</p>
        </div>
    </div>
</body>
</html>
