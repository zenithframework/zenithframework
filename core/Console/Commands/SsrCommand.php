<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Http\SsrEngine;

/**
 * SSR Engine Management Command
 * 
 * Manages Server-Side Rendering cache, prerendering, and statistics
 */
class SsrCommand extends Command
{
    protected string $name = 'ssr';
    protected string $description = 'Manage SSR engine (cache, prerender, stats)';

    public function handle(Container $container, array $arguments): void
    {
        $action = $arguments[0] ?? 'stats';

        match ($action) {
            'stats' => $this->showStats(),
            'clear' => $this->clearCache(),
            'prerender' => $this->prerenderPages($arguments),
            'warm' => $this->warmCache($arguments),
            'invalidate' => $this->invalidateCache($arguments),
            'render' => $this->renderPage($arguments),
            'help' => $this->showHelp(),
            default => $this->showHelp(),
        };
    }

    /**
     * Show SSR cache statistics
     */
    protected function showStats(): void
    {
        $this->info('📊 SSR Engine Statistics');
        $this->line('');

        $stats = SsrEngine::getCacheStats();

        $this->line('  Total Files:    ' . $stats['total_files']);
        $this->line('  Total Size:     ' . $stats['total_size']);
        $this->line('  Valid Files:    ' . $stats['valid_files']);
        $this->line('  Expired Files:  ' . $stats['expired_files']);
        $this->line('');

        if ($stats['total_files'] > 0) {
            $this->info('✅ Cache is active');
        } else {
            $this->warn('⚠️ No cached SSR pages yet');
        }

        $this->line('');
    }

    /**
     * Clear all SSR cache
     */
    protected function clearCache(): void
    {
        $this->warn('🗑️ Clearing all SSR cache...');

        $result = SsrEngine::clearAllCache();

        if ($result) {
            $this->info('✅ SSR cache cleared successfully');
        } else {
            $this->error('❌ Failed to clear SSR cache');
        }

        $this->line('');
    }

    /**
     * Prerender pages
     */
    protected function prerenderPages(array $arguments): void
    {
        if (count($arguments) < 2) {
            $this->error('Usage: php zen ssr prerender "page1,page2,page3"');
            $this->line('');
            return;
        }

        $pagesList = explode(',', $arguments[1]);
        $pages = [];

        foreach ($pagesList as $page) {
            $page = trim($page);
            $pages[] = [
                'template' => $page,
                'data' => [],
                'options' => ['cache' => true],
            ];
        }

        $this->info('🚀 Prerendering ' . count($pages) . ' page(s)...');
        $this->line('');

        $results = SsrEngine::prerender($pages);

        foreach ($results['pages'] as $template => $result) {
            if ($result['status'] === 'success') {
                $this->info("  ✓ {$template} ({$result['size']} bytes)");
            } else {
                $this->error("  ✗ {$template}: {$result['message']}");
            }
        }

        $this->line('');
        $this->info("📊 Results: {$results['cached']} cached, {$results['failed']} failed");
        $this->info("⏱️  Total Time: {$results['total_time']}");
        $this->line('');
    }

    /**
     * Warm cache with pages
     */
    protected function warmCache(array $arguments): void
    {
        if (count($arguments) < 2) {
            $this->error('Usage: php zen ssr warm "page1,page2,page3"');
            $this->line('');
            return;
        }

        $pagesList = explode(',', $arguments[1]);
        $pages = [];

        foreach ($pagesList as $page) {
            $page = trim($page);
            $pages[] = [
                'template' => $page,
                'data' => [],
                'options' => ['cache' => true, 'ttl' => 7200],
            ];
        }

        $this->info('🔥 Warming SSR cache with ' . count($pages) . ' page(s)...');
        $this->line('');

        $results = SsrEngine::warmCache($pages);

        $successCount = count(array_filter($results['pages'], fn($r) => $r['status'] === 'success'));
        $this->info("✅ {$successCount} page(s) cached successfully");
        $this->info("⏱️  Total Time: {$results['total_time']}");
        $this->line('');
    }

    /**
     * Invalidate specific cache
     */
    protected function invalidateCache(array $arguments): void
    {
        if (count($arguments) < 2) {
            $this->error('Usage: php zen ssr invalidate <cache_key>');
            $this->line('');
            return;
        }

        $cacheKey = $arguments[1];
        $this->info("🔄 Invalidating cache key: {$cacheKey}");

        $result = SsrEngine::invalidateCache($cacheKey);

        if ($result) {
            $this->info('✅ Cache invalidated successfully');
        } else {
            $this->warn('⚠️ Cache key not found or already invalid');
        }

        $this->line('');
    }

    /**
     * Render a specific page
     */
    protected function renderPage(array $arguments): void
    {
        if (count($arguments) < 2) {
            $this->error('Usage: php zen ssr render <template>');
            $this->line('');
            return;
        }

        $template = $arguments[1];
        $this->info("📄 Rendering: {$template}");
        $this->line('');

        try {
            $html = SsrEngine::render($template, [], ['cache' => true]);
            $this->info('✅ Rendered successfully');
            $this->line("📏 Size: " . strlen($html) . " bytes");
            $this->line('');

            // Show first 500 chars of HTML
            $preview = substr($html, 0, 500);
            $this->line('📝 Preview:');
            $this->line($preview . (strlen($html) > 500 ? '...' : ''));
            $this->line('');
        } catch (\Throwable $e) {
            $this->error('❌ Failed to render: ' . $e->getMessage());
        }
    }

    /**
     * Show help
     */
    protected function showHelp(): void
    {
        $this->info('📖 SSR Engine Usage');
        $this->line('');
        $this->line('  php zen ssr stats                          Show SSR cache statistics');
        $this->line('  php zen ssr clear                          Clear all SSR cache');
        $this->line('  php zen ssr prerender "page1,page2"        Prerender and cache pages');
        $this->line('  php zen ssr warm "page1,page2"             Warm cache with pages');
        $this->line('  php zen ssr invalidate <cache_key>         Invalidate specific cache');
        $this->line('  php zen ssr render <template>              Render and preview page');
        $this->line('  php zen ssr help                           Show this help');
        $this->line('');
        $this->info('Examples:');
        $this->line('');
        $this->line('  php zen ssr stats');
        $this->line('  php zen ssr prerender "home,about,contact"');
        $this->line('  php zen ssr clear');
        $this->line('');
    }
}
