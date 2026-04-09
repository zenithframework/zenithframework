<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Http\SsrEngine;
use Zenith\Http\IsrEngine;

class CacheSsr extends Command
{
    protected string $name = 'cache:ssr';
    protected string $description = 'Manage SSR/ISR cache (clear, stats, warm, revalidate)';

    public function handle(Container $container, array $arguments): void
    {
        $action = $arguments['action'] ?? 'stats';
        $type = $arguments['type'] ?? 'all';

        match ($action) {
            'clear' => $this->clearCache($type),
            'stats' => $this->showStats($type),
            'warm' => $this->warmCache(),
            'revalidate' => $this->revalidate($arguments['path'] ?? null),
            default => $this->showHelp(),
        };
    }

    protected function clearCache(string $type): void
    {
        $this->info("Clearing {$type} cache...");

        $results = [];

        if ($type === 'ssr' || $type === 'all') {
            $success = SsrEngine::clearAllCache();
            $results['SSR'] = $success ? '✓ Cleared' : '✗ Failed';
        }

        if ($type === 'isr' || $type === 'all') {
            $success = IsrEngine::clearAllCache();
            $results['ISR'] = $success ? '✓ Cleared' : '✗ Failed';
        }

        foreach ($results as $cacheType => $status) {
            $this->line("  {$cacheType}: {$status}");
        }

        $this->info('Cache cleared successfully!');
    }

    protected function showStats(string $type): void
    {
        $this->info('Cache Statistics:');
        $this->line();

        if ($type === 'ssr' || $type === 'all') {
            $ssrStats = SsrEngine::getCacheStats();
            $this->line('  <comment>SSR Cache:</comment>');
            $this->line("    Total Files: {$ssrStats['total_files']}");
            $this->line("    Valid Files: {$ssrStats['valid_files']}");
            $this->line("    Expired Files: {$ssrStats['expired_files']}");
            $this->line("    Total Size: {$ssrStats['total_size']}");
            $this->line();
        }

        if ($type === 'isr' || $type === 'all') {
            $isrStats = IsrEngine::getCacheStats();
            $this->line('  <comment>ISR Cache:</comment>');
            $this->line("    Total Pages: {$isrStats['total_pages']}");
            $this->line("    Valid Pages: {$isrStats['valid_pages']}");
            $this->line("    Expired Pages: {$isrStats['expired_pages']}");
            $this->line("    Total Size: {$isrStats['total_size']}");
            $this->line();

            if (!empty($isrStats['pages'])) {
                $this->line('  <comment>Cached Pages:</comment>');
                foreach (array_slice($isrStats['pages'], 0, 10) as $page) {
                    $status = $page['is_expired'] ? '<error>EXPIRED</error>' : '<info>VALID</info>';
                    $this->line("    - {$page['key']} [{$status}] ({$page['size']} bytes)");
                    $this->line("      Cached: {$page['cached_at']}, Expires: {$page['expires_at']}");
                }

                if (count($isrStats['pages']) > 10) {
                    $this->line("    ... and " . (count($isrStats['pages']) - 10) . " more");
                }
            }
        }
    }

    protected function warmCache(): void
    {
        $this->info('Warming cache...');
        $this->warn('Note: Please define pages to warm in config/ssr.php');
        $this->line();

        // Example configuration
        $pages = [
            // [
            //     'template' => 'pages/home',
            //     'data' => [],
            //     'options' => ['cache' => true, 'ttl' => 3600],
            // ],
        ];

        if (empty($pages)) {
            $this->warn('No pages configured for warming. Please configure pages in config/ssr.php');
            return;
        }

        $result = SsrEngine::warmCache($pages);

        $this->info("Prerendered {$result['cached']} pages in {$result['total_time']}");
        
        if ($result['failed'] > 0) {
            $this->error("{$result['failed']} pages failed to render");
        }
    }

    protected function revalidate(?string $path): void
    {
        if (!$path) {
            $this->warn('Please specify a path to revalidate: cache:ssr revalidate /path');
            return;
        }

        $this->info("Revalidating: {$path}");
        
        // This would typically trigger a revalidation job
        $success = IsrEngine::revalidate($path, []);
        
        if ($success) {
            $this->info('✓ Page revalidated successfully!');
        } else {
            $this->error('✗ Failed to revalidate page');
        }
    }

    protected function showHelp(): void
    {
        $this->info('Usage:');
        $this->line('  php zen cache:ssr [action] [type]');
        $this->line();
        
        $this->info('Actions:');
        $this->line('  stats         Show cache statistics (default)');
        $this->line('  clear         Clear SSR/ISR cache');
        $this->line('  warm          Warm cache with configured pages');
        $this->line('  revalidate    Revalidate specific path');
        $this->line();
        
        $this->info('Types:');
        $this->line('  all           Both SSR and ISR (default)');
        $this->line('  ssr           SSR cache only');
        $this->line('  isr           ISR cache only');
        $this->line();
        
        $this->info('Examples:');
        $this->line('  php zen cache:ssr stats');
        $this->line('  php zen cache:ssr stats isr');
        $this->line('  php zen cache:ssr clear');
        $this->line('  php zen cache:ssr clear ssr');
        $this->line('  php zen cache:ssr warm');
        $this->line('  php zen cache:ssr revalidate /blog/post-1');
    }
}
