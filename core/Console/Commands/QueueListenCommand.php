<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueListenCommand extends Command
{
    protected string $name = 'queue:listen';

    protected string $description = 'Listen to queue events';

    public function handle(Container $container, array $arguments): void
    {
        $queue = 'default';
        $sleep = 3;

        foreach ($arguments as $arg) {
            if (preg_match('/^--queue=(.+)$/', $arg, $matches)) {
                $queue = $matches[1];
            } elseif (preg_match('/^--sleep=(\d+)$/', $arg, $matches)) {
                $sleep = (int) $matches[1];
            }
        }

        $storagePath = dirname(__DIR__, 3) . '/storage/queue';
        $queueFile = $storagePath . '/jobs.json';
        $logFile = $storagePath . '/queue.log';

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        if (!file_exists($queueFile)) {
            file_put_contents($queueFile, json_encode([]));
        }

        $this->info("Listening to queue events...");
        $this->line("  Queue:  {$queue}");
        $this->line("  Sleep:  {$sleep}s");
        $this->line("  Log:    {$logFile}");
        $this->line('');
        $this->warn("Press Ctrl+C to stop listening.");
        $this->line('');

        $lastCount = 0;

        while (true) {
            $jobs = json_decode(file_get_contents($queueFile), true) ?: [];
            $currentCount = count($jobs);

            if ($currentCount !== $lastCount) {
                $timestamp = date('Y-m-d H:i:s');

                if ($currentCount > $lastCount) {
                    $added = $currentCount - $lastCount;
                    $message = "[{$timestamp}] {$added} new job(s) added to queue. Total: {$currentCount}";
                    $this->info("  {$message}");
                } else {
                    $removed = $lastCount - $currentCount;
                    $message = "[{$timestamp}] {$removed} job(s) processed. Remaining: {$currentCount}";
                    $this->line("  {$message}");
                }

                $logEntry = $message . PHP_EOL;
                file_put_contents($logFile, $logEntry, FILE_APPEND);

                $lastCount = $currentCount;
            }

            sleep($sleep);
        }
    }
}
