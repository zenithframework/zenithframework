<?php

declare(strict_types=1);

namespace Zenith\Resilience;

class CircuitBreaker
{
    protected string $name;
    protected int $failureThreshold = 5;
    protected int $timeout = 60;
    protected int $successThreshold = 2;
    protected string $state = 'closed';
    protected int $failureCount = 0;
    protected int $successCount = 0;
    protected int $lastFailureTime = 0;

    public function __construct(string $name = 'default')
    {
        $this->name = $name;
    }

    public function call(callable $callback, ?callable $fallback = null): mixed
    {
        if ($this->state === 'open') {
            if ($this->shouldAttemptReset()) {
                $this->state = 'half-open';
            } elseif ($fallback !== null) {
                return $fallback();
            } else {
                throw new \RuntimeException("Circuit breaker [{$this->name}] is open");
            }
        }

        try {
            $result = $callback();
            $this->onSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->onFailure();
            
            if ($fallback !== null && $this->state === 'open') {
                return $fallback();
            }
            
            throw $e;
        }
    }

    public function onSuccess(): void
    {
        $this->failureCount = 0;
        
        if ($this->state === 'half-open') {
            $this->successCount++;
            
            if ($this->successCount >= $this->successThreshold) {
                $this->state = 'closed';
                $this->successCount = 0;
            }
        }
    }

    public function onFailure(): void
    {
        $this->failureCount++;
        $this->lastFailureTime = time();
        
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = 'open';
        }
    }

    protected function shouldAttemptReset(): bool
    {
        return (time() - $this->lastFailureTime) >= $this->timeout;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isClosed(): bool
    {
        return $this->state === 'closed';
    }

    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    public function isHalfOpen(): bool
    {
        return $this->state === 'half-open';
    }

    public function reset(): void
    {
        $this->state = 'closed';
        $this->failureCount = 0;
        $this->successCount = 0;
        $this->lastFailureTime = 0;
    }

    public function setFailureThreshold(int $threshold): void
    {
        $this->failureThreshold = $threshold;
    }

    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }

    public function setSuccessThreshold(int $threshold): void
    {
        $this->successThreshold = $threshold;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    public function getLastFailureTime(): int
    {
        return $this->lastFailureTime;
    }
}

class FallbackHandler
{
    protected array $handlers = [];
    protected array $fallbacks = [];

    public function register(string $service, callable $handler): void
    {
        $this->handlers[$service] = $handler;
    }

    public function fallback(string $service, array $params = []): mixed
    {
        if (isset($this->fallbacks[$service])) {
            return ($this->fallbacks[$service])($params);
        }

        if (isset($this->handlers[$service])) {
            try {
                return ($this->handlers[$service])();
            } catch (\Throwable $e) {
                if (isset($this->fallbacks[$service])) {
                    return ($this->fallbacks[$service])(['error' => $e->getMessage()]);
                }
                throw $e;
            }
        }

        throw new \RuntimeException("No handler or fallback for service: {$service}");
    }

    public function setFallback(string $service, callable $fallback): void
    {
        $this->fallbacks[$service] = $fallback;
    }

    public function hasFallback(string $service): bool
    {
        return isset($this->fallbacks[$service]);
    }

    public function remove(string $service): void
    {
        unset($this->handlers[$service], $this->fallbacks[$service]);
    }
}

class RetryPolicy
{
    protected int $maxAttempts = 3;
    protected int $baseDelay = 100;
    protected int $maxDelay = 5000;
    protected float $multiplier = 2.0;
    protected array $retryableExceptions = [];

    public function __construct(array $config = [])
    {
        $this->maxAttempts = $config['max_attempts'] ?? 3;
        $this->baseDelay = $config['base_delay'] ?? 100;
        $this->maxDelay = $config['max_delay'] ?? 5000;
        $this->multiplier = $config['multiplier'] ?? 2.0;
        $this->retryableExceptions = $config['retryable'] ?? [\Exception::class];
    }

    public function execute(callable $callback): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxAttempts) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;
                
                if (!$this->isRetryable($e)) {
                    throw $e;
                }
                
                $attempt++;
                
                if ($attempt >= $this->maxAttempts) {
                    break;
                }
                
                usleep($this->getDelay($attempt) * 1000);
            }
        }

        throw $lastException;
    }

    protected function isRetryable(\Throwable $e): bool
    {
        foreach ($this->retryableExceptions as $exception) {
            if ($e instanceof $exception) {
                return true;
            }
        }
        return false;
    }

    protected function getDelay(int $attempt): int
    {
        $delay = (int) ($this->baseDelay * pow($this->multiplier, $attempt - 1));
        return min($delay, $this->maxDelay);
    }
}

class TimeoutHandler
{
    public static function run(callable $callback, int $timeoutMs = 1000): mixed
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeoutMs / 1000,
            ],
        ]);

        if (function_exists('set_time_limit')) {
            $previous = ini_get('max_execution_time');
            set_time_limit($timeoutMs / 1000);
        }

        try {
            return $callback();
        } finally {
            if (isset($previous) && $previous !== false && function_exists('set_time_limit')) {
                set_time_limit((int) $previous);
            }
        }
    }

    public static function withTimeout(callable $callback, int $timeoutMs, callable $onTimeout): mixed
    {
        $result = null;
        $timedOut = false;

        $oldHandler = set_error_handler(function ($errno, $errstr) use (&$timedOut) {
            if ($errno === E_WARNING && str_contains($errstr, 'Maximum execution time')) {
                $timedOut = true;
                return true;
            }
            return false;
        });

        $start = (int) (microtime(true) * 1000);

        try {
            while (true) {
                $result = $callback();
                
                if ($timedOut) {
                    return $onTimeout();
                }
                
                $current = (int) (microtime(true) * 1000);
                if ($current - $start >= $timeoutMs) {
                    return $onTimeout();
                }
                
                break;
            }
        } finally {
            restore_error_handler();
        }

        return $result;
    }
}

class Bulkhead
{
    protected int $maxExecutions;
    protected int $maxQueue;
    protected int $queueTimeout;
    protected int $currentExecutions = 0;
    protected array $queue = [];

    public function __construct(int $maxExecutions = 10, int $maxQueue = 100, int $queueTimeout = 5000)
    {
        $this->maxExecutions = $maxExecutions;
        $this->maxQueue = $maxQueue;
        $this->queueTimeout = $queueTimeout;
    }

    public function execute(callable $callback): mixed
    {
        if ($this->currentExecutions >= $this->maxExecutions) {
            if (count($this->queue) >= $this->maxQueue) {
                throw new \RuntimeException('Bulkhead queue is full');
            }

            return $this->enqueue($callback);
        }

        $this->currentExecutions++;

        try {
            return $callback();
        } finally {
            $this->currentExecutions--;
            $this->processQueue();
        }
    }

    protected function enqueue(callable $callback): mixed
    {
        $startTime = microtime(true);
        
        while (count($this->queue) >= $this->maxQueue || $this->currentExecutions >= $this->maxExecutions) {
            if ((microtime(true) - $startTime) * 1000 > $this->queueTimeout) {
                throw new \RuntimeException('Bulkhead timeout waiting for execution slot');
            }
            
            usleep(1000);
        }

        $this->currentExecutions++;

        try {
            return $callback();
        } finally {
            $this->currentExecutions--;
        }
    }

    protected function processQueue(): void
    {
    }

    public function getCurrentExecutions(): int
    {
        return $this->currentExecutions;
    }

    public function getQueueSize(): int
    {
        return count($this->queue);
    }

    public function isAvailable(): bool
    {
        return $this->currentExecutions < $this->maxExecutions;
    }
}