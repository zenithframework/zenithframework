<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueFlushCommand extends Command
{
    protected string $name = 'queue:flush';

    protected string $description = 'Flush all failed queue jobs';

    public function handle(Container $container, array $arguments): void
    {
        $force = in_array('--force', $arguments);

        if (!$force) {
            $confirm = $this->confirm('This will remove all failed queue jobs. Continue?');

            if (!$confirm) {
                $this->warn('Flush cancelled.');
                return;
            }
        }

        $failedPath = dirname(__DIR__, 3) . '/storage/queue/failed.json';

        if (!file_exists($failedPath)) {
            $this->warn('No failed jobs file found.');
            return;
        }

        $failedJobs = json_decode(file_get_contents($failedPath), true) ?: [];
        $count = count($failedJobs);

        if ($count === 0) {
            $this->warn('No failed jobs to flush.');
            return;
        }

        if (file_put_contents($failedPath, json_encode([], JSON_PRETTY_PRINT)) === false) {
            $this->error('Failed to flush failed jobs.');
            return;
        }

        $this->info("Flushed {$count} failed job(s).");
    }
}
