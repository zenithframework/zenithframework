<?php

declare(strict_types=1);

namespace Zenith\Queue;

use Zenith\Database\QueryBuilder;

abstract class Job
{
    public string $id;
    public string $queue = 'default';
    public int $attempts = 0;
    public int $maxAttempts = 3;
    public int $timeout = 60;
    public array $payload = [];
    public ?string $reservedAt = null;
    public ?string $availableAt = null;

    abstract public function handle(): void;

    public function failed(\Throwable $exception): void
    {
    }

    public function retryAfter(): int
    {
        return pow(2, $this->attempts);
    }

    public static function dispatch(array $data = []): void
    {
        $job = new static();
        $job->payload = $data;
        
        if (class_exists('Zen\Queue\QueueManager')) {
            app(\Zenith\Queue\QueueManager::class)->push(static::class, $data);
        }
    }

    public static function dispatchSync(array $data = []): void
    {
        $job = new static();
        $job->payload = $data;
        $job->handle();
    }
}