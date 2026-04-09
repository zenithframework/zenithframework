<?php

declare(strict_types=1);

namespace Zenith\Queue;

use Zenith\Database\QueryBuilder;

interface QueueDriver
{
    public function push(string $job, array $data = [], ?string $queue = null): string;
    public function pop(?string $queue = null): ?JobPayload;
    public function release(JobPayload $payload, int $delay = 0): void;
    public function delete(JobPayload $payload): void;
}

class JobPayload
{
    public function __construct(
        public string $id,
        public string $job,
        public array $data,
        public int $attempts,
        public ?string $reservedAt = null,
        public ?string $availableAt = null
    ) {
    }
}

class QueueManager
{
    protected ?QueueDriver $driver = null;
    protected string $defaultQueue = 'default';
    protected bool $shouldDispatchEvents = true;

    public function __construct()
    {
        $this->initDriver();
    }

    protected function initDriver(): void
    {
        $config = config('queue') ?? ['default' => 'sync', 'drivers' => []];
        $driverName = $config['default'] ?? 'sync';

        $this->driver = match ($driverName) {
            'database' => new DatabaseQueueDriver(),
            'redis' => new RedisQueueDriver(),
            default => new SyncDriver(),
        };
    }

    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        return $this->driver->push($job, $data, $queue ?? $this->defaultQueue);
    }

    public function later(int $delay, string $job, array $data = [], ?string $queue = null): string
    {
        $availableAt = time() + $delay;
        return $this->driver->push($job, $data, $queue ?? $this->defaultQueue, $availableAt);
    }

    public function pop(?string $queue = null): ?JobPayload
    {
        return $this->driver->pop($queue ?? $this->defaultQueue);
    }

    public function work(?string $queue = null, int $maxAttempts = 3): void
    {
        while (true) {
            $payload = $this->pop($queue);

            if ($payload === null) {
                usleep(100000);
                continue;
            }

            $this->process($payload, $maxAttempts);
        }
    }

    public function process(JobPayload $payload, int $maxAttempts = 3): void
    {
        try {
            $job = $this->resolveJob($payload->job);
            $job->id = $payload->id;
            $job->payload = $payload->data;
            $job->attempts = $payload->attempts;
            $job->maxAttempts = $maxAttempts;
            $job->handle();

            $this->driver->delete($payload);
        } catch (\Throwable $e) {
            $payload->attempts++;

            if ($payload->attempts >= $maxAttempts) {
                if (method_exists($job ?? null, 'failed')) {
                    $job->failed($e);
                }
                $this->driver->delete($payload);
            } else {
                $delay = (new \ReflectionClass($job))->hasMethod('retryAfter')
                    ? $job->retryAfter()
                    : pow(2, $payload->attempts);
                $this->driver->release($payload, $delay);
            }
        }
    }

    protected function resolveJob(string $job): Job
    {
        if ($job instanceof Job) {
            return $job;
        }

        if (class_exists($job)) {
            return new $job();
        }

        throw new \RuntimeException("Job class [{$job}] does not exist");
    }

    public function getDriver(): QueueDriver
    {
        return $this->driver;
    }
}

class SyncDriver implements QueueDriver
{
    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        $jobInstance = new $job();
        $jobInstance->payload = $data;
        $jobInstance->handle();
        return uniqid('sync_');
    }

    public function pop(?string $queue = null): ?JobPayload
    {
        return null;
    }

    public function release(JobPayload $payload, int $delay = 0): void
    {
    }

    public function delete(JobPayload $payload): void
    {
    }
}

class DatabaseQueueDriver implements QueueDriver
{
    protected QueryBuilder $db;

    public function __construct()
    {
        $this->db = new QueryBuilder();
        $this->ensureTable();
    }

    protected function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS jobs (
            id TEXT PRIMARY KEY,
            job TEXT NOT NULL,
            data TEXT NOT NULL,
            attempts INTEGER DEFAULT 0,
            reserved_at INTEGER,
            available_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL
        )";
        $this->db->raw($sql);
    }

    public function push(string $job, array $data = [], ?string $queue = null, ?int $availableAt = null): string
    {
        $id = uniqid('job_');
        $availableAt = $availableAt ?? time();

        $this->db->table('jobs')->insert([
            'id' => $id,
            'job' => $job,
            'data' => json_encode($data),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => $availableAt,
            'created_at' => time(),
        ]);

        return $id;
    }

    public function pop(?string $queue = null): ?JobPayload
    {
        $job = $this->db->table('jobs')
            ->where('reserved_at', 'IS', null)
            ->where('available_at', '<=', time())
            ->orderBy('available_at', 'ASC')
            ->first();

        if ($job === false) {
            return null;
        }

        $this->db->table('jobs')
            ->where('id', $job['id'])
            ->update(['reserved_at' => time()]);

        return new JobPayload(
            $job['id'],
            $job['job'],
            json_decode($job['data'], true),
            (int) $job['attempts'],
            !empty($job['reserved_at']) ? (string) $job['reserved_at'] : null,
            (string) $job['available_at']
        );
    }

    public function release(JobPayload $payload, int $delay = 0): void
    {
        $this->db->table('jobs')
            ->where('id', $payload->id)
            ->update([
                'reserved_at' => null,
                'available_at' => time() + $delay,
                'attempts' => $payload->attempts,
            ]);
    }

    public function delete(JobPayload $payload): void
    {
        $this->db->table('jobs')->where('id', $payload->id)->delete();
    }
}

class RedisQueueDriver implements QueueDriver
{
    protected object|null $redis = null;

    public function __construct()
    {
        $redisClass = 'Redis';
        if (class_exists($redisClass)) {
            $config = config('queue')['drivers']['redis'] ?? [];
            $this->redis = new $redisClass();
            try {
                $this->redis->connect($config['host'] ?? '127.0.0.1', (int) ($config['port'] ?? 6379));
            } catch (\Exception $e) {
                $this->redis = null;
            }
        }
    }

    public function push(string $job, array $data = [], ?string $queue = null): string
    {
        if ($this->redis === null) {
            throw new \RuntimeException('Redis not available');
        }

        $id = uniqid('job_');
        $payload = json_encode([
            'id' => $id,
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
        ]);

        $this->redis->rpush("queue:{$queue}", $payload);
        return $id;
    }

    public function pop(?string $queue = null): ?JobPayload
    {
        if ($this->redis === null) {
            return null;
        }

        $data = $this->redis->lpop("queue:{$queue}");
        if ($data === false || $data === null) {
            return null;
        }

        $payload = json_decode($data, true);
        return new JobPayload(
            $payload['id'],
            $payload['job'],
            $payload['data'],
            $payload['attempts']
        );
    }

    public function release(JobPayload $payload, int $delay = 0): void
    {
        if ($this->redis !== null) {
            $this->redis->rpush("queue:delayed", json_encode($payload));
        }
    }

    public function delete(JobPayload $payload): void
    {
    }
}