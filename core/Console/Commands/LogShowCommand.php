<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class LogShowCommand extends Command
{
    protected string $name = 'log:show';

    protected string $description = 'Show recent log entries';

    public function handle(Container $container, array $arguments): void
    {
        $logPath = dirname(__DIR__, 3) . '/storage/logs';

        if (!is_dir($logPath)) {
            $this->warn('Log directory does not exist.');
            return;
        }

        $lines = 50;
        $level = null;

        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--lines=')) {
                $lines = (int) substr($arg, 8);
            } elseif (in_array($arg, ['--error', '--warn', '--info'])) {
                $level = trim($arg, '-');
            }
        }

        $logFile = $logPath . '/app.log';

        if (!file_exists($logFile)) {
            $logFiles = glob($logPath . '/*.log');

            if (empty($logFiles)) {
                $this->warn('No log files found.');
                return;
            }

            $logFile = end($logFiles);
        }

        $this->info("Showing last {$lines} lines from: " . basename($logFile));
        $this->line(str_repeat('-', 80));

        $content = file_get_contents($logFile);
        $allLines = explode("\n", $content);
        $recentLines = array_slice($allLines, -$lines);

        foreach ($recentLines as $line) {
            if ($level !== null && !str_contains(strtolower($line), strtolower($level))) {
                continue;
            }

            if (str_contains(strtolower($line), 'error')) {
                $this->error($line);
            } elseif (str_contains(strtolower($line), 'warn')) {
                $this->warn($line);
            } elseif (str_contains(strtolower($line), 'info')) {
                $this->info($line);
            } else {
                $this->line($line);
            }
        }

        $this->line(str_repeat('-', 80));
    }
}
