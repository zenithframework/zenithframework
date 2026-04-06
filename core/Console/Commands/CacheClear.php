<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class CacheClear extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $driver = $arguments[0] ?? null;
        $all = in_array('--all', $arguments);

        if ($all) {
            $this->clearAll();
            return;
        }

        if ($driver === 'file' || $driver === null) {
            $this->clearFileCache();
        }

        if ($driver === 'array') {
            $this->warn('Array cache is in-memory only, cleared on each request.');
            return;
        }

        $this->info('Cache cleared successfully.');
    }

    protected function clearFileCache(): void
    {
        $cachePath = dirname(__DIR__, 2) . '/cache';

        if (!is_dir($cachePath)) {
            $this->warn('No cache directory found.');
            return;
        }

        $files = glob($cachePath . '/*.cache');
        $count = 0;

        foreach ($files ?? [] as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        $this->info("Cleared {$count} file cache items.");
    }

    protected function clearAll(): void
    {
        $this->clearFileCache();
        $this->info('All cache cleared.');
    }
}
