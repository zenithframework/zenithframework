<?php

declare(strict_types=1);

namespace Zenith\Http;

use Zenith\Cache\Cache;

class SsrEngine
{
    protected static string $cachePath = 'storage/ssr/';
    protected static array $config = [];
    protected static bool $initialized = false;

    protected static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        try {
            $ssrConfig = config('ssr.ssr', []);
            self::$config = [
                'enabled' => $ssrConfig['enabled'] ?? true,
                'ttl' => $ssrConfig['ttl'] ?? 3600,
            ];
            self::$cachePath = $ssrConfig['cache_path'] ?? 'storage/ssr/';
        } catch (\Exception $e) {
            // Use defaults if config not available
            self::$config = ['enabled' => true, 'ttl' => 3600];
        }
    }

    public static function configure(array $config): void
    {
        self::$initialized = true;
        self::$config = $config;
        self::$cachePath = $config['cache_path'] ?? 'storage/ssr/';
    }

    public static function render(string $template, array $data = [], array $options = []): string
    {
        self::ensureInitialized();
        $startTime = microtime(true);
        
        // Check if SSR caching is enabled
        if (self::isSsrEnabled() && isset($options['cache']) && $options['cache'] === true) {
            $cacheKey = self::generateCacheKey($template, $data);
            $cachedHtml = self::getCachedHtml($cacheKey);
            
            if ($cachedHtml !== null) {
                return $cachedHtml;
            }
        }

        // Render the template
        $html = self::renderTemplate($template, $data);

        // Cache the rendered HTML if SSR caching is enabled
        if (self::isSsrEnabled() && isset($options['cache']) && $options['cache'] === true) {
            $cacheKey = self::generateCacheKey($template, $data);
            $ttl = $options['ttl'] ?? 3600; // Default 1 hour
            self::cacheHtml($cacheKey, $html, $ttl);
        }

        // Add performance headers in debug mode
        if (config('app.debug', false)) {
            $renderTime = (microtime(true) - $startTime) * 1000;
            $html .= "\n<!-- SSR Render Time: {$renderTime}ms -->";
        }

        return $html;
    }

    public static function renderPage(string $page, array $data = [], array $options = []): string
    {
        return self::render($page, $data, $options);
    }

    public static function prerender(array $pages): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($pages as $pageConfig) {
            $template = $pageConfig['template'];
            $data = $pageConfig['data'] ?? [];
            $options = $pageConfig['options'] ?? ['cache' => true];

            try {
                $html = self::render($template, $data, $options);
                $results[$template] = [
                    'status' => 'success',
                    'size' => strlen($html),
                ];
            } catch (\Exception $e) {
                $results[$template] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $totalTime = (microtime(true) - $startTime) * 1000;
        
        return [
            'pages' => $results,
            'total_time' => round($totalTime, 2) . 'ms',
            'cached' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
            'failed' => count(array_filter($results, fn($r) => $r['status'] === 'error')),
        ];
    }

    public static function getCachedHtml(string $cacheKey): ?string
    {
        $cacheFile = self::getCacheFilePath($cacheKey);
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        if (!$data || !isset($data['expires_at'])) {
            return null;
        }

        // Check if cache is expired
        if (time() > $data['expires_at']) {
            return null;
        }

        return $data['html'];
    }

    public static function cacheHtml(string $cacheKey, string $html, int $ttl = 3600): void
    {
        $cacheDir = dirname(self::getCacheFilePath($cacheKey));
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $data = [
            'html' => $html,
            'cached_at' => time(),
            'expires_at' => time() + $ttl,
            'ttl' => $ttl,
        ];

        file_put_contents(
            self::getCacheFilePath($cacheKey),
            json_encode($data),
            LOCK_EX
        );
    }

    public static function invalidateCache(string $cacheKey): bool
    {
        $cacheFile = self::getCacheFilePath($cacheKey);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }

    public static function clearAllCache(): bool
    {
        if (!is_dir(self::$cachePath)) {
            return true;
        }

        return self::deleteDirectory(self::$cachePath);
    }

    public static function getCacheStats(): array
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'expired_files' => 0,
            'valid_files' => 0,
        ];

        if (!is_dir(self::$cachePath)) {
            return $stats;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$cachePath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();

                $content = file_get_contents($file->getPathname());
                $data = json_decode($content, true);

                if ($data && isset($data['expires_at'])) {
                    if (time() > $data['expires_at']) {
                        $stats['expired_files']++;
                    } else {
                        $stats['valid_files']++;
                    }
                }
            }
        }

        $stats['total_size'] = self::formatBytes($stats['total_size']);
        
        return $stats;
    }

    public static function warmCache(array $pages): array
    {
        return self::prerender($pages);
    }

    protected static function renderTemplate(string $template, array $data = []): string
    {
        // Use the existing view() helper for rendering
        return view($template, $data);
    }

    protected static function generateCacheKey(string $template, array $data = []): string
    {
        $dataHash = md5(serialize($data));
        return md5($template . '_' . $dataHash);
    }

    protected static function getCacheFilePath(string $cacheKey): string
    {
        return self::$cachePath . $cacheKey . '.json';
    }

    protected static function isSsrEnabled(): bool
    {
        return self::$config['enabled'] ?? true;
    }

    protected static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }

    protected static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
