<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class ViewListCommand extends Command
{
    protected string $name = 'view:list';

    protected string $description = 'List all view files organized by type';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $viewsDir = dirname(__DIR__, 3) . '/views';

        if (!is_dir($viewsDir)) {
            $this->warn('No views directory found at views/');
            return;
        }

        $views = $this->discoverViews($viewsDir);

        if (empty($views)) {
            $this->warn('No view files found.');
            return;
        }

        if ($json) {
            echo json_encode($views, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('View Files');
        $this->line(str_repeat('-', 80));

        foreach ($views as $type => $files) {
            $this->line('');
            $this->info("  [{$type}] (" . count($files) . " files)");

            foreach ($files as $file) {
                $size = $this->formatBytes($file['size']);
                $this->line("    - {$file['name']} ({$size})");
            }
        }

        $totalFiles = array_sum(array_map('count', $views));
        $this->line('');
        $this->line(str_repeat('-', 80));
        $this->info('Total view files: ' . $totalFiles);
    }

    protected function discoverViews(string $directory): array
    {
        $views = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $type = dirname($relativePath);

                if ($type === '.') {
                    $type = 'root';
                }

                if (!isset($views[$type])) {
                    $views[$type] = [];
                }

                $views[$type][] = [
                    'name' => $relativePath,
                    'size' => $file->getSize(),
                    'path' => $file->getPathname(),
                ];
            }
        }

        ksort($views);

        foreach ($views as $type => $files) {
            usort($views[$type], fn($a, $b) => strcmp($a['name'], $b['name']));
        }

        return $views;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $bytes = max($bytes, 0);

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
