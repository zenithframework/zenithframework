<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class StorageStatsCommand extends Command
{
    protected string $name = 'storage:stats';

    protected string $description = 'Show storage usage statistics';

    public function handle(Container $container, array $arguments): void
    {
        $basePath = dirname(__DIR__, 3);
        $storagePath = $basePath . '/storage';

        if (!is_dir($storagePath)) {
            $this->warn('Storage directory does not exist.');
            return;
        }

        $this->info('Calculating storage usage...');
        $this->line('');

        $this->line(str_repeat('-', 60));
        $this->info('Storage Usage Statistics');
        $this->line(str_repeat('-', 60));

        $totalSize = $this->getDirectorySize($storagePath);
        $totalFiles = $this->countFiles($storagePath);

        $this->line("  Total Size:  " . $this->formatBytes($totalSize));
        $this->line("  Total Files: {$totalFiles}");
        $this->line('');

        $directories = glob($storagePath . '/*', GLOB_ONLYDIR);

        if (!empty($directories)) {
            $this->info('Breakdown by directory:');
            $this->line('');

            $stats = [];

            foreach ($directories as $dir) {
                $name = basename($dir);
                $size = $this->getDirectorySize($dir);
                $files = $this->countFiles($dir);
                $stats[] = ['name' => $name, 'size' => $size, 'files' => $files];
            }

            usort($stats, fn($a, $b) => $b['size'] <=> $a['size']);

            foreach ($stats as $stat) {
                $percentage = $totalSize > 0 ? round(($stat['size'] / $totalSize) * 100, 1) : 0;
                $this->line(sprintf(
                    "  %-20s %10s  (%5.1f%%)  %d files",
                    $stat['name'],
                    $this->formatBytes($stat['size']),
                    $percentage,
                    $stat['files']
                ));
            }
        }

        $this->line(str_repeat('-', 60));
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;

        if (!is_dir($path)) {
            return 0;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    protected function countFiles(string $path): int
    {
        $count = 0;

        if (!is_dir($path)) {
            return 0;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
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
