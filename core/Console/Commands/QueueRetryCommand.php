<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueRetryCommand extends Command
{
    protected string $name = 'queue:retry';

    protected string $description = 'Retry failed queue job';

    public function handle(Container $container, array $arguments): void
    {
        $id = $arguments[0] ?? null;

        if ($id === null) {
            $id = $this->ask('Enter the failed job ID to retry');
        }

        if (empty($id)) {
            $this->error('Job ID is required.');
            return;
        }

        $failedPath = dirname(__DIR__, 3) . '/storage/queue/failed.json';

        if (!file_exists($failedPath)) {
            $this->error('No failed jobs file found.');
            return;
        }

        $failedJobs = json_decode(file_get_contents($failedPath), true) ?: [];
        $index = (int) $id - 1;

        if (!isset($failedJobs[$index])) {
            $this->error("Failed job with ID [{$id}] not found.");
            return;
        }

        $job = $failedJobs[$index];
        $jobClass = $job['job'] ?? null;

        if ($jobClass === null || !class_exists($jobClass)) {
            $this->error("Job class [{$jobClass}] not found.");
            return;
        }

        try {
            $jobInstance = new $jobClass();
            $payload = $job['payload'] ?? [];

            if (method_exists($jobInstance, 'handle')) {
                $jobInstance->handle(...array_values($payload));
            }

            unset($failedJobs[$index]);
            $failedJobs = array_values($failedJobs);

            file_put_contents($failedPath, json_encode($failedJobs, JSON_PRETTY_PRINT));

            $this->info("Job [{$jobClass}] retried successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to retry job [{$jobClass}]: " . $e->getMessage());
        }
    }
}
