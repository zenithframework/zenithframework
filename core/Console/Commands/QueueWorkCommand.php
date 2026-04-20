<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';

    protected string $description = 'Start queue worker';

    public function handle(Container $container, array $arguments): void
    {
        $queue = 'default';
        $sleep = 3;
        $tries = 0;
        $timeout = 60;

        foreach ($arguments as $arg) {
            if (preg_match('/^--queue=(.+)$/', $arg, $matches)) {
                $queue = $matches[1];
            } elseif (preg_match('/^--sleep=(\d+)$/', $arg, $matches)) {
                $sleep = (int) $matches[1];
            } elseif (preg_match('/^--tries=(\d+)$/', $arg, $matches)) {
                $tries = (int) $matches[1];
            } elseif (preg_match('/^--timeout=(\d+)$/', $arg, $matches)) {
                $timeout = (int) $matches[1];
            }
        }

        $once = in_array('--once', $arguments);

        $this->info("Starting queue worker...");
        $this->line("  Queue:   {$queue}");
        $this->line("  Sleep:   {$sleep}s");
        $this->line("  Tries:   " . ($tries === 0 ? 'unlimited' : $tries));
        $this->line("  Timeout: {$timeout}s");
        $this->line("  Once:    " . ($once ? 'Yes' : 'No'));
        $this->line('');

        $storagePath = dirname(__DIR__, 3) . '/storage/queue';
        $queueFile = $storagePath . '/jobs.json';

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        if (!file_exists($queueFile)) {
            file_put_contents($queueFile, json_encode([]));
        }

        $processed = 0;
        $maxJobs = $once ? 1 : PHP_INT_MAX;

        while ($processed < $maxJobs) {
            $jobs = json_decode(file_get_contents($queueFile), true) ?: [];

            if (empty($jobs)) {
                if ($once) {
                    $this->warn('No jobs in queue.');
                    break;
                }

                sleep($sleep);
                continue;
            }

            $job = array_shift($jobs);
            $jobClass = $job['job'] ?? null;

            if ($jobClass === null || !class_exists($jobClass)) {
                $this->warn("Skipping invalid job: {$jobClass}");
                file_put_contents($queueFile, json_encode($jobs, JSON_PRETTY_PRINT));
                continue;
            }

            try {
                $this->line("Processing: {$jobClass}");
                $instance = new $jobClass();

                if (method_exists($instance, 'handle')) {
                    $payload = $job['payload'] ?? [];
                    $instance->handle(...array_values($payload));
                }

                $this->info("Processed: {$jobClass}");
                $processed++;
                file_put_contents($queueFile, json_encode($jobs, JSON_PRETTY_PRINT));
            } catch (\Throwable $e) {
                $this->error("Failed: {$jobClass} - " . $e->getMessage());

                $job['attempts'] = ($job['attempts'] ?? 0) + 1;
                $job['last_error'] = $e->getMessage();

                if ($tries > 0 && $job['attempts'] >= $tries) {
                    $this->warn("Job {$jobClass} exceeded max attempts, moving to failed queue.");
                    $this->moveToFailedQueue($job);
                } else {
                    $jobs[] = $job;
                }

                file_put_contents($queueFile, json_encode($jobs, JSON_PRETTY_PRINT));
            }

            if (!$once) {
                usleep($sleep * 1000000);
            }
        }

        $this->info("Queue worker stopped. Processed: {$processed} job(s).");
    }

    protected function moveToFailedQueue(array $job): void
    {
        $failedPath = dirname(__DIR__, 3) . '/storage/queue/failed.json';

        if (!is_dir(dirname($failedPath))) {
            mkdir(dirname($failedPath), 0755, true);
        }

        $failedJobs = [];

        if (file_exists($failedPath)) {
            $failedJobs = json_decode(file_get_contents($failedPath), true) ?: [];
        }

        $job['failed_at'] = date('Y-m-d H:i:s');
        $failedJobs[] = $job;

        file_put_contents($failedPath, json_encode($failedJobs, JSON_PRETTY_PRINT));
    }
}
