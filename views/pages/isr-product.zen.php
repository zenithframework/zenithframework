<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['name'] ?? 'Product') ?> - ISR Example</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .product-card { background: #1e293b; border-radius: 1rem; padding: 2rem; margin-top: 2rem; }
        .product-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem; }
        .product-name { font-size: 2rem; color: #60a5fa; }
        .product-price { font-size: 2rem; font-weight: bold; color: #10b981; }
        .product-description { color: #94a3b8; margin-bottom: 1.5rem; line-height: 1.8; }
        .product-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .meta-item { background: #0f172a; padding: 1rem; border-radius: 0.5rem; text-align: center; }
        .meta-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem; }
        .meta-value { font-size: 1.25rem; font-weight: 600; }
        .isr-badge { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 0.5rem 1rem; border-radius: 9999px; font-weight: 600; display: inline-block; margin: 1rem 0; }
        .info-box { background: #1e293b; border-left: 4px solid #f59e0b; padding: 1.5rem; margin-top: 2rem; border-radius: 0 0.5rem 0.5rem 0; }
        .info-box h3 { color: #f59e0b; margin-bottom: 0.5rem; }
        .info-box ul { list-style: none; padding-left: 0; }
        .info-box li { margin-bottom: 0.5rem; padding-left: 1.5rem; position: relative; }
        .info-box li:before { content: "✓"; position: absolute; left: 0; color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <div class="product-card">
            <div class="product-header">
                <h1 class="product-name"><?= e($product['name']) ?></h1>
                <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
            </div>

            <p class="product-description"><?= e($product['description']) ?></p>

            <div class="product-meta">
                <div class="meta-item">
                    <div class="meta-label">Product ID</div>
                    <div class="meta-value">#<?= e($product['id']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Stock</div>
                    <div class="meta-value"><?= e($product['stock']) ?> units</div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Status</div>
                    <div class="meta-value" style="color: <?= $product['stock'] > 0 ? '#10b981' : '#ef4444' ?>">
                        <?= $product['stock'] > 0 ? '✅ In Stock' : '❌ Out of Stock' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="info-box">
            <h3>🔄 Incremental Static Regeneration (ISR)</h3>
            <p style="color: #94a3b8; margin-bottom: 1rem;">
                This page uses ISR with stale-while-revalidate pattern:
            </p>
            <ul>
                <li>First request: Page is generated and cached</li>
                <li>Subsequent requests: Served from cache (instant)</li>
                <li>After TTL: Serves stale page while regenerating</li>
                <li>Background regeneration updates the cache</li>
                <li>Perfect balance of speed and freshness</li>
            </ul>
            <div class="isr-badge">⚡ ISR Active - 60s TTL</div>
        </div>
    </div>
</body>
</html>
