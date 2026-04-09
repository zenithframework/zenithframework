<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class LogClearCommand extends Command
{
    protected string $name = 'log:clear';

    protected string $description = 'Clear all log files';

    public function handle(Container $container, array $arguments): void
    {
        $logPath = dirname(__DIR__, 3) . '/storage/logs';

        if (!is_dir($logPath)) {
            $this->warn('Log directory does not exist.');
            return;
        }

        $force = in_array('--force', $arguments);

        if (!$force) {
            $confirm = $this->confirm('This will delete all log files. Continue?');

            if (!$confirm) {
                $this->warn('Log clear cancelled.');
                return;
            }
        }

        $files = glob($logPath . '/*.log');
        $count = 0;

        foreach ($files ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
                $count++;
            }
        }

        if ($count === 0) {
            $this->warn('No log files found to clear.');
            return;
        }

        $this->info("Cleared {$count} log file(s) successfully.");
    }
}
