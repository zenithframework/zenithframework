# Zenith Framework - SSR Engine Guide

> **Server-Side Rendering with Caching** - High-Performance Template Delivery

---

## 📖 Overview

The SSR Engine provides:
- ✅ Server-side template rendering
- ✅ Automatic HTML caching
- ✅ Cache warming and prerendering
- ✅ TTL-based expiration
- ✅ Cache invalidation
- ✅ Performance metrics
- ✅ Directory management

---

## 🚀 Quick Start

### Basic Usage

```php
use Zen\Http\SsrEngine;

// Render a template
$html = SsrEngine::render('home', ['title' => 'Welcome']);

// Render with caching
$html = SsrEngine::render('home', ['title' => 'Welcome'], [
    'cache' => true,
    'ttl' => 3600, // 1 hour
]);
```

### Configuration

```php
// Configure SSR engine
SsrEngine::configure([
    'enabled' => true,
    'ttl' => 3600,          // Default cache TTL (1 hour)
    'cache_path' => 'storage/ssr/',
]);
```

---

## 🛠️ CLI Commands

### Show SSR Statistics

```bash
php zen ssr stats
```

**Output:**
```
📊 SSR Engine Statistics

  Total Files:    15
  Total Size:     2.5 MB
  Valid Files:    12
  Expired Files:  3

✅ Cache is active
```

### Clear All Cache

```bash
php zen ssr clear
```

### Prerender Pages

```bash
# Prerender multiple pages
php zen ssr prerender "home,about,contact,products"

# Output:
🚀 Prerendering 4 page(s)...

  ✓ home (15234 bytes)
  ✓ about (8456 bytes)
  ✓ contact (6789 bytes)
  ✓ products (23456 bytes)

📊 Results: 4 cached, 0 failed
⏱️  Total Time: 245.67ms
```

### Warm Cache

```bash
# Warm cache with pages (longer TTL: 2 hours)
php zen ssr warm "home,about,contact"
```

### Render and Preview Page

```bash
# Render a specific template
php zen ssr render home

# Output:
📄 Rendering: home

✅ Rendered successfully
📏 Size: 15234 bytes

📝 Preview:
<!DOCTYPE html>
<html>
<head>...</head>
...
```

### Invalidate Specific Cache

```bash
php zen ssr invalidate <cache_key>
```

---

## 💡 API Reference

### `render(string $template, array $data = [], array $options = []): string`

Render a template with optional caching.

```php
// Basic render
$html = SsrEngine::render('home');

// Render with data
$html = SsrEngine::render('user/profile', ['user' => $user]);

// Render with caching
$html = SsrEngine::render('home', [], [
    'cache' => true,
    'ttl' => 7200, // 2 hours
]);
```

### `renderPage(string $page, array $data = [], array $options = []): string`

Alias for `render()`.

### `prerender(array $pages): array`

Prerender multiple pages.

```php
$pages = [
    ['template' => 'home', 'data' => [], 'options' => ['cache' => true]],
    ['template' => 'about', 'data' => ['title' => 'About'], 'options' => ['cache' => true, 'ttl' => 7200]],
];

$results = SsrEngine::prerender($pages);

// Returns:
[
    'pages' => [
        'home' => ['status' => 'success', 'size' => 15234],
        'about' => ['status' => 'success', 'size' => 8456],
    ],
    'total_time' => '245.67ms',
    'cached' => 2,
    'failed' => 0,
]
```

### `warmCache(array $pages): array`

Alias for `prerender()`.

### `getCachedHtml(string $cacheKey): ?string`

Get cached HTML by key.

```php
$html = SsrEngine::getCachedHtml('abc123');
```

### `cacheHtml(string $cacheKey, string $html, int $ttl = 3600): void`

Manually cache HTML.

```php
SsrEngine::cacheHtml('custom_key', '<html>Custom</html>', 7200);
```

### `invalidateCache(string $cacheKey): bool`

Invalidate specific cache entry.

```php
$deleted = SsrEngine::invalidateCache('custom_key');
```

### `clearAllCache(): bool`

Clear all SSR cache.

```php
$cleared = SsrEngine::clearAllCache();
```

### `getCacheStats(): array`

Get cache statistics.

```php
$stats = SsrEngine::getCacheStats();

// Returns:
[
    'total_files' => 15,
    'total_size' => '2.5 MB',
    'expired_files' => 3,
    'valid_files' => 12,
]
```

---

## 🎯 Use Cases

### 1. Static Page Caching

```php
// Cache static pages for 1 hour
$html = SsrEngine::render('home', [], ['cache' => true]);
```

### 2. Product Page Caching

```php
// Cache product pages for 30 minutes
$html = SsrEngine::render('product/detail', [
    'product' => $product
], [
    'cache' => true,
    'ttl' => 1800,
]);
```

### 3. Cache Warming on Deployment

```bash
# In deployment script
php zen ssr clear
php zen ssr warm "home,about,products,contact"
```

### 4. Cache Invalidation on Update

```php
// When content changes
SsrEngine::invalidateCache($cacheKey);

// Or clear all
SsrEngine::clearAllCache();
```

---

## ⚙️ Configuration

### Config File

```php
// config/ssr.php
return [
    'enabled' => true,
    'ttl' => 3600,              // Default TTL (1 hour)
    'cache_path' => 'storage/ssr/',
];
```

### Environment Variables

```env
SSR_ENABLED=true
SSR_TTL=3600
SSR_CACHE_PATH=storage/ssr/
```

---

## 📊 Performance Tips

### 1. Enable Caching for Static Pages

```php
// Good: Static pages benefit most
SsrEngine::render('home', [], ['cache' => true]);

// Avoid: Highly dynamic pages
SsrEngine::render('dashboard', ['user' => $user]); // Don't cache
```

### 2. Set Appropriate TTL

```php
// News: Short TTL (5 minutes)
['cache' => true, 'ttl' => 300]

// About page: Long TTL (24 hours)
['cache' => true, 'ttl' => 86400]

// Products: Medium TTL (1 hour)
['cache' => true, 'ttl' => 3600]
```

### 3. Warm Cache Before Traffic Spikes

```bash
# Before marketing campaign
php zen ssr warm "landing,pricing,features"
```

### 4. Monitor Cache Stats

```bash
php zen ssr stats
```

---

## 🧪 Testing

Run SSR tests:

```bash
# Direct test
php test_ssr.php

# Via CLI
php zen ssr stats
php zen ssr prerender "test"
```

---

## 🔒 Security Notes

- Cache files stored in `storage/ssr/` (not web-accessible)
- Cache keys are MD5 hashes (no template names exposed)
- File permissions: 0755 for directories
- Use `LOCK_EX` for concurrent write safety

---

## 📈 Metrics

| Metric | Value |
|--------|-------|
| **Render Time** | Tracked in debug mode |
| **Cache Hit Rate** | Visible in stats |
| **Storage Usage** | Tracked automatically |
| **Expiration** | TTL-based |

---

**SSR Engine - High-Performance Template Delivery** 🚀
