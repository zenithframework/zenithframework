<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Blog') ?> - SSR Example</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { font-size: 2.5rem; margin-bottom: 2rem; text-align: center; }
        .post-list { display: grid; gap: 1.5rem; }
        .post-card { background: #1e293b; border-radius: 0.5rem; padding: 1.5rem; transition: transform 0.2s; }
        .post-card:hover { transform: translateY(-4px); }
        .post-title { font-size: 1.5rem; margin-bottom: 0.5rem; color: #60a5fa; }
        .post-excerpt { color: #94a3b8; margin-bottom: 1rem; }
        .post-meta { font-size: 0.875rem; color: #64748b; }
        .badge { display: inline-block; background: #3b82f6; color: white; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; margin-left: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 SSR Blog</h1>
        
        <div class="post-list">
            @foreach($posts as $post)
                <div class="post-card">
                    <h3 class="post-title"><?= e($post['title']) ?></h3>
                    <p class="post-excerpt"><?= e($post['excerpt']) ?></p>
                    <div class="post-meta">
                        <span>📅 Published</span>
                        <span class="badge">SSR Cached</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top: 3rem; text-align: center; padding: 2rem; background: #1e293b; border-radius: 0.5rem;">
            <p style="color: #94a3b8;">This blog list is cached with SSR for 30 minutes</p>
            <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.5rem;">⚡ Subsequent requests will serve cached HTML</p>
        </div>
    </div>
</body>
</html>
