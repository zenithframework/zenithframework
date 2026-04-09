<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class ViewCacheCommand extends Command
{
    protected string $name = 'view:cache';

    protected string $description = 'Clear view template cache';

    public function handle(Container $container, array $arguments): void
    {
        $cacheDir = dirname(__DIR__, 3) . '/storage/framework/views';
        $force = in_array('--force', $arguments);

        if (!is_dir($cacheDir)) {
            $this->warn('No view cache directory found.');
            return;
        }

        if (!$force) {
            $this->line("View cache directory: {$cacheDir}");
        }

        $files = glob($cacheDir . '/*');
        $count = 0;

        if ($files === false) {
            $this->warn('No cached views found.');
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }

        if ($count === 0) {
            $this->warn('No cached views to clear.');
        } else {
            $this->info("Cleared {$count} cached view(s).");
        }
    }
}
