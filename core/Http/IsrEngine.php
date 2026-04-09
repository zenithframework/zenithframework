<?php

declare(strict_types=1);

namespace Zenith\Http;

class IsrEngine
{
    protected static string $cachePath = 'storage/isr/';
    protected static array $config = [];
    protected static array $revalidationCallbacks = [];
    protected static bool $initialized = false;

    protected static function ensureInitialized(): void
    {
        if (self::$initialized) {
            return;
        }
        
        self::$initialized = true;
        
        try {
            $isrConfig = config('ssr.isr', []);
            self::$config = [
                'enabled' => $isrConfig['enabled'] ?? true,
                'ttl' => $isrConfig['ttl'] ?? 60,
                'background_revalidation' => $isrConfig['background_revalidation'] ?? true,
            ];
            self::$cachePath = $isrConfig['cache_path'] ?? 'storage/isr/';
            
            // Register callbacks
            $callbacks = $isrConfig['callbacks'] ?? [];
            foreach ($callbacks as $pattern => $callbackList) {
                foreach ($callbackList as $callback) {
                    self::onRevalidate($pattern, $callback);
                }
            }
        } catch (\Exception $e) {
            // Use defaults
            self::$config = [
                'enabled' => true,
                'ttl' => 60,
                'background_revalidation' => true,
            ];
        }
    }

    public static function configure(array $config): void
    {
        self::$initialized = true;
        self::$config = $config;
        self::$cachePath = $config['cache_path'] ?? 'storage/isr/';
    }

    /**
     * Handle ISR request with stale-while-revalidate pattern
     */
    public static function handle(string $template, array $data = [], array $options = []): IsrResponse
    {
        self::ensureInitialized();
        $startTime = microtime(true);
        $cacheKey = self::generateCacheKey($template, $data);
        
        // Get cached response if exists
        $cachedResponse = self::getCachedResponse($cacheKey);
        
        // Determine if we need to revalidate
        $shouldRevalidate = self::shouldRevalidate($cacheKey, $cachedResponse, $options);
        
        // If we have cached data and don't need to revalidate, return it immediately
        if ($cachedResponse !== null && !$shouldRevalidate) {
            $renderTime = (microtime(true) - $startTime) * 1000;
            $cachedResponse->addHeader('X-ISR', 'HIT');
            $cachedResponse->addHeader('X-Render-Time', round($renderTime, 2) . 'ms');
            
            return $cachedResponse;
        }

        // If we have stale data, return it and schedule background revalidation
        if ($cachedResponse !== null && $shouldRevalidate) {
            self::scheduleRevalidation($template, $data, $cacheKey, $options);
            
            $renderTime = (microtime(true) - $startTime) * 1000;
            $cachedResponse->addHeader('X-ISR', 'STALE');
            $cachedResponse->addHeader('X-Render-Time', round($renderTime, 2) . 'ms');
            
            return $cachedResponse;
        }

        // No cached data, generate fresh response
        $html = self::renderTemplate($template, $data);
        $ttl = $options['ttl'] ?? 60; // Default 60 seconds for ISR
        $status = $options['status'] ?? 200;
        $headers = $options['headers'] ?? [];

        $response = new IsrResponse($html, $status, $headers);
        self::cacheResponse($cacheKey, $response, $ttl);

        $renderTime = (microtime(true) - $startTime) * 1000;
        $response->addHeader('X-ISR', 'MISS');
        $response->addHeader('X-Render-Time', round($renderTime, 2) . 'ms');

        return $response;
    }

    /**
     * Revalidate a specific cache key immediately
     */
    public static function revalidate(string $template, array $data = [], array $options = []): bool
    {
        $cacheKey = self::generateCacheKey($template, $data);
        
        try {
            $html = self::renderTemplate($template, $data);
            $ttl = $options['ttl'] ?? 60;
            
            $response = new IsrResponse($html, 200, []);
            self::cacheResponse($cacheKey, $response, $ttl);
            
            // Trigger revalidation callbacks
            self::triggerRevalidationCallbacks($template, $data);
            
            return true;
        } catch (\Exception $e) {
            error_log("ISR Revalidation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register a callback to be triggered on revalidation
     */
    public static function onRevalidate(string $pattern, callable $callback): void
    {
        self::$revalidationCallbacks[$pattern][] = $callback;
    }

    /**
     * Get ISR cache stats
     */
    public static function getCacheStats(): array
    {
        $stats = [
            'total_pages' => 0,
            'total_size' => 0,
            'expired_pages' => 0,
            'valid_pages' => 0,
            'pages' => [],
        ];

        if (!is_dir(self::$cachePath)) {
            return $stats;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$cachePath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $stats['total_pages']++;
                $stats['total_size'] += $file->getSize();

                $content = file_get_contents($file->getPathname());
                $data = json_decode($content, true);

                if ($data && isset($data['expires_at'])) {
                    $isExpired = time() > $data['expires_at'];
                    
                    if ($isExpired) {
                        $stats['expired_pages']++;
                    } else {
                        $stats['valid_pages']++;
                    }

                    $stats['pages'][] = [
                        'key' => $file->getBasename('.json'),
                        'cached_at' => date('Y-m-d H:i:s', $data['cached_at']),
                        'expires_at' => date('Y-m-d H:i:s', $data['expires_at']),
                        'is_expired' => $isExpired,
                        'size' => strlen($data['html']),
                    ];
                }
            }
        }

        $stats['total_size'] = self::formatBytes($stats['total_size']);
        
        return $stats;
    }

    /**
     * Clear all ISR cache
     */
    public static function clearAllCache(): bool
    {
        if (!is_dir(self::$cachePath)) {
            return true;
        }

        return self::deleteDirectory(self::$cachePath);
    }

    /**
     * Clear specific cache key
     */
    public static function invalidateCache(string $cacheKey): bool
    {
        $cacheFile = self::getCacheFilePath($cacheKey);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }

    /**
     * Warm ISR cache with specific pages
     */
    public static function warmCache(array $pages): array
    {
        $results = [];
        $startTime = microtime(true);

        foreach ($pages as $pageConfig) {
            $template = $pageConfig['template'];
            $data = $pageConfig['data'] ?? [];
            $options = $pageConfig['options'] ?? ['ttl' => 60];

            try {
                $response = self::handle($template, $data, $options);
                $results[$template] = [
                    'status' => 'success',
                    'size' => strlen($response->getContent()),
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

    /**
     * Check if cache should be revalidated
     */
    protected static function shouldRevalidate(string $cacheKey, ?IsrResponse $cachedResponse, array $options): bool
    {
        if ($cachedResponse === null) {
            return false;
        }

        $cacheFile = self::getCacheFilePath($cacheKey);
        
        if (!file_exists($cacheFile)) {
            return false;
        }

        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        if (!$data || !isset($data['expires_at'])) {
            return true;
        }

        // Check if expired
        if (time() > $data['expires_at']) {
            return true;
        }

        // Check if forced revalidation
        return $options['force_revalidate'] ?? false;
    }

    /**
     * Schedule background revalidation
     */
    protected static function scheduleRevalidation(string $template, array $data, string $cacheKey, array $options): void
    {
        // For now, we'll do synchronous revalidation
        // In production, this should use a job queue or async process
        $html = self::renderTemplate($template, $data);
        $ttl = $options['ttl'] ?? 60;
        
        $response = new IsrResponse($html, 200, []);
        self::cacheResponse($cacheKey, $response, $ttl);
        
        // Trigger revalidation callbacks
        self::triggerRevalidationCallbacks($template, $data);
    }

    /**
     * Trigger revalidation callbacks
     */
    protected static function triggerRevalidationCallbacks(string $template, array $data): void
    {
        foreach (self::$revalidationCallbacks as $pattern => $callbacks) {
            if (fnmatch($pattern, $template)) {
                foreach ($callbacks as $callback) {
                    try {
                        $callback($template, $data);
                    } catch (\Exception $e) {
                        error_log("ISR Callback Error: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Get cached response
     */
    protected static function getCachedResponse(string $cacheKey): ?IsrResponse
    {
        $cacheFile = self::getCacheFilePath($cacheKey);
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        if (!$data) {
            return null;
        }

        return IsrResponse::fromJson($data);
    }

    /**
     * Cache response
     */
    protected static function cacheResponse(string $cacheKey, IsrResponse $response, int $ttl): void
    {
        $cacheDir = dirname(self::getCacheFilePath($cacheKey));
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $data = $response->toJson();
        $data['cached_at'] = time();
        $data['expires_at'] = time() + $ttl;
        $data['ttl'] = $ttl;

        file_put_contents(
            self::getCacheFilePath($cacheKey),
            json_encode($data),
            LOCK_EX
        );
    }

    /**
     * Render template using existing view system
     */
    protected static function renderTemplate(string $template, array $data = []): string
    {
        return view($template, $data);
    }

    /**
     * Generate cache key
     */
    protected static function generateCacheKey(string $template, array $data = []): string
    {
        $dataHash = md5(serialize($data));
        return md5($template . '_' . $dataHash);
    }

    /**
     * Get cache file path
     */
    protected static function getCacheFilePath(string $cacheKey): string
    {
        return self::$cachePath . $cacheKey . '.json';
    }

    /**
     * Delete directory recursively
     */
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

    /**
     * Format bytes
     */
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
