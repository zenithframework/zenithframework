<?php

declare(strict_types=1);

namespace Zen\Security\RateLimit;

class TokenBucket
{
    protected float $capacity;
    protected float $tokens;
    protected float $fillRate;
    protected int $lastRefill;

    public function __construct(int $capacity = 100, int $refillRate = 10)
    {
        $this->capacity = (float) $capacity;
        $this->tokens = $this->capacity;
        $this->fillRate = (float) $refillRate;
        $this->lastRefill = time();
    }

    public function consume(int $tokens = 1): bool
    {
        $this->refill();

        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;
            return true;
        }

        return false;
    }

    public function tryConsume(int $tokens = 1): array
    {
        $this->refill();

        $available = (int) $this->tokens;
        
        if ($available >= $tokens) {
            $this->tokens -= $tokens;
            return ['allowed' => true, 'remaining' => $available - $tokens];
        }

        $waitTime = ($tokens - $available) / $this->fillRate;
        
        return [
            'allowed' => false,
            'remaining' => $available,
            'retry_after' => (int) ceil($waitTime),
        ];
    }

    protected function refill(): void
    {
        $now = time();
        $elapsed = $now - $this->lastRefill;
        
        $newTokens = $elapsed * $this->fillRate;
        $this->tokens = min($this->capacity, $this->tokens + $newTokens);
        $this->lastRefill = $now;
    }

    public function getTokens(): float
    {
        $this->refill();
        return $this->tokens;
    }

    public function reset(): void
    {
        $this->tokens = $this->capacity;
        $this->lastRefill = time();
    }
}

class LeakyBucket
{
    protected float $capacity;
    protected float $level = 0;
    protected float $leakRate;
    protected int $lastLeak;

    public function __construct(int $capacity = 100, int $leakRate = 1)
    {
        $this->capacity = (float) $capacity;
        $this->leakRate = (float) $leakRate;
        $this->lastLeak = time();
    }

    public function add(): bool
    {
        $this->leak();

        if ($this->level < $this->capacity) {
            $this->level++;
            return true;
        }

        return false;
    }

    public function tryAdd(): array
    {
        $this->leak();

        $available = (int) ($this->capacity - $this->level);
        
        if ($available > 0) {
            $this->level++;
            return ['allowed' => true, 'remaining' => $available - 1];
        }

        $timeToEmpty = (int) ceil($this->level / $this->leakRate);
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $timeToEmpty,
        ];
    }

    protected function leak(): void
    {
        $now = time();
        $elapsed = $now - $this->lastLeak;
        
        $leaked = $elapsed * $this->leakRate;
        $this->level = max(0, $this->level - $leaked);
        $this->lastLeak = $now;
    }

    public function getLevel(): float
    {
        $this->leak();
        return $this->level;
    }
}

class SlidingWindow
{
    protected array $timestamps = [];
    protected int $maxRequests;
    protected int $windowSize;

    public function __construct(int $maxRequests = 100, int $windowSize = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSize = $windowSize;
    }

    public function isAllowed(): bool
    {
        $now = time();
        $this->cleanup($now);

        if (count($this->timestamps) < $this->maxRequests) {
            $this->timestamps[] = $now;
            return true;
        }

        return false;
    }

    public function tryRequest(): array
    {
        $now = time();
        $this->cleanup($now);

        $count = count($this->timestamps);
        
        if ($count < $this->maxRequests) {
            $this->timestamps[] = $now;
            return [
                'allowed' => true,
                'remaining' => $this->maxRequests - $count - 1,
                'reset_at' => $now + $this->windowSize,
            ];
        }

        $oldest = $this->timestamps[0];
        $timeUntilReset = $this->windowSize - ($now - $oldest);
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => max(1, $timeUntilReset),
            'reset_at' => $oldest + $this->windowSize,
        ];
    }

    protected function cleanup(int $now): void
    {
        $cutoff = $now - $this->windowSize;
        $this->timestamps = array_values(array_filter($this->timestamps, fn($ts) => $ts > $cutoff));
    }

    public function reset(): void
    {
        $this->timestamps = [];
    }

    public function getCount(): int
    {
        $this->cleanup(time());
        return count($this->timestamps);
    }
}

class QuotaManager
{
    protected array $quotas = [];
    protected array $usage = [];

    public function setQuota(string $key, int $limit, int $period = 3600): void
    {
        $this->quotas[$key] = [
            'limit' => $limit,
            'period' => $period,
            'reset_at' => time() + $period,
        ];
    }

    public function consume(string $key, int $amount = 1): bool
    {
        if (!isset($this->quotas[$key])) {
            return true;
        }

        $quota = $this->quotas[$key];

        if (time() >= $quota['reset_at']) {
            $this->resetQuota($key);
            $quota = $this->quotas[$key];
        }

        if (!isset($this->usage[$key])) {
            $this->usage[$key] = 0;
        }

        if ($this->usage[$key] + $amount <= $quota['limit']) {
            $this->usage[$key] += $amount;
            return true;
        }

        return false;
    }

    public function getRemaining(string $key): int
    {
        if (!isset($this->quotas[$key]) || !isset($this->usage[$key])) {
            return $this->quotas[$key]['limit'] ?? 0;
        }

        return max(0, $this->quotas[$key]['limit'] - $this->usage[$key]);
    }

    public function getUsage(string $key): int
    {
        return $this->usage[$key] ?? 0;
    }

    protected function resetQuota(string $key): void
    {
        $this->usage[$key] = 0;
        $this->quotas[$key]['reset_at'] = time() + $this->quotas[$key]['period'];
    }

    public function reset(string $key): void
    {
        if (isset($this->usage[$key])) {
            $this->usage[$key] = 0;
        }
    }

    public function resetAll(): void
    {
        $this->usage = [];
        foreach ($this->quotas as $key => $quota) {
            $this->quotas[$key]['reset_at'] = time() + $quota['period'];
        }
    }
}

class PriorityQueueLimiter
{
    protected array $queues = [];
    protected int $maxConcurrent = 100;

    public function __construct(int $maxConcurrent = 100)
    {
        $this->maxConcurrent = $maxConcurrent;
    }

    public function enqueue(string $priority, callable $task): void
    {
        if (!isset($this->queues[$priority])) {
            $this->queues[$priority] = [];
        }

        $this->queues[$priority][] = $task;
    }

    public function process(int $maxPerPriority = 10): array
    {
        $results = [];
        krsort($this->queues);

        $processed = 0;
        
        foreach ($this->queues as $priority => $tasks) {
            if ($processed >= $this->maxConcurrent) {
                break;
            }

            $toProcess = array_splice($tasks, 0, min($maxPerPriority, count($tasks)));
            $this->queues[$priority] = $tasks;

            foreach ($toProcess as $task) {
                $results[] = $task();
                $processed++;
            }
        }

        return $results;
    }

    public function getStats(): array
    {
        $stats = [];
        
        foreach ($this->queues as $priority => $tasks) {
            $stats[$priority] = count($tasks);
        }

        return [
            'queues' => $stats,
            'total_pending' => array_sum($stats),
            'max_concurrent' => $this->maxConcurrent,
        ];
    }
}