<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class DebugClearCommand extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $this->info("🗑️ Clearing Debug Cache and Logs\n");

        // Clear view cache
        $viewCachePath = dirname(__DIR__, 3) . '/storage/framework/views';
        if (is_dir($viewCachePath)) {
            $files = glob($viewCachePath . '/*.php');
            $count = count($files);
            foreach ($files as $file) {
                @unlink($file);
            }
            $this->line("✓ Cleared {$count} view cache files");
        }

        // Clear log files
        $logPath = dirname(__DIR__, 3) . '/storage/logs';
        if (is_dir($logPath)) {
            $files = glob($logPath . '/*.log');
            $count = count($files);
            foreach ($files as $file) {
                @unlink($file);
            }
            $this->line("✓ Cleared {$count} log files");
        }

        $this->info("\n✓ Debug cache and logs cleared successfully!");
    }
}
