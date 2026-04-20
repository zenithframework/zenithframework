<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Cache\Cache;

class CacheStatsCommand extends Command
{
    protected string $name = 'cache:stats';

    protected string $description = 'Show cache statistics';

    public function handle(Container $container, array $arguments): void
    {
        $driver = env('CACHE_DRIVER', 'file');
        $cachePath = Cache::getPath();

        $this->line(str_repeat('-', 60));
        $this->info('Cache Statistics');
        $this->line(str_repeat('-', 60));
        $this->line("  Driver:    {$driver}");

        if ($driver === 'file') {
            $this->showFileCacheStats($cachePath);
        } elseif ($driver === 'array') {
            $this->line("  Storage:   In-memory (cleared on each request)");
            $this->warn("  Array cache stats are not persistent.");
        } else {
            $this->warn("  No statistics available for driver: {$driver}");
        }

        $this->line(str_repeat('-', 60));
    }

    protected function showFileCacheStats(string $cachePath): void
    {
        if (!is_dir($cachePath)) {
            $this->line("  Path:      {$cachePath}");
            $this->warn("  Cache directory does not exist.");
            return;
        }

        $files = glob($cachePath . '/*.cache');
        $totalFiles = count($files ?: []);
        $totalSize = 0;
        $validCount = 0;
        $expiredCount = 0;

        foreach ($files ?: [] as $file) {
            $totalSize += filesize($file);

            $content = file_get_contents($file);
            $data = @unserialize($content);

            if (is_array($data) && isset($data['expiry'])) {
                if ($data['expiry'] < time()) {
                    $expiredCount++;
                } else {
                    $validCount++;
                }
            }
        }

        $this->line("  Path:      {$cachePath}");
        $this->line("  Total:     {$totalFiles} files");
        $this->line("  Valid:     {$validCount}");
        $this->line("  Expired:   {$expiredCount}");
        $this->line("  Size:      " . $this->formatBytes($totalSize));
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
