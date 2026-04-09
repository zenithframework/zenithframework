<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueFailedCommand extends Command
{
    protected string $name = 'queue:failed';

    protected string $description = 'List failed queue jobs';

    public function handle(Container $container, array $arguments): void
    {
        $failedPath = dirname(__DIR__, 3) . '/storage/queue/failed.json';

        if (!file_exists($failedPath)) {
            $this->warn('No failed jobs found.');
            return;
        }

        $failedJobs = json_decode(file_get_contents($failedPath), true) ?: [];

        if (empty($failedJobs)) {
            $this->warn('No failed jobs found.');
            return;
        }

        $this->line(str_repeat('-', 90));
        $this->info('Failed Queue Jobs');
        $this->line(str_repeat('-', 90));

        foreach ($failedJobs as $index => $job) {
            $id = str_pad((string) ($index + 1), 5);
            $jobClass = $job['job'] ?? 'Unknown';
            $failedAt = $job['failed_at'] ?? 'Unknown';
            $error = $job['error'] ?? 'Unknown error';

            $this->line("  {$id} | Class: {$jobClass}");
            $this->line("       | Failed: {$failedAt}");
            $this->line("       | Error:  {$error}");
            $this->line(str_repeat('-', 90));
        }

        $this->info("Total: " . count($failedJobs) . " failed job(s)");
        $this->line('');
        $this->warn('Use php zen queue:retry <id> to retry a failed job');
    }
}
