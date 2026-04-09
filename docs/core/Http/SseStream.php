<?php

declare(strict_types=1);

namespace Zenith\Http;

use Generator;
use Zenith\Http\SseEvent;

/**
 * SSE Stream Controller
 * 
 * Manages Server-Sent Events stream lifecycle with support for:
 * - Polling mode (dataProvider closure)
 * - Blocking mode (subscription/pubsub)
 * - Automatic heartbeats
 * - Connection timeouts
 * - Last-Event-ID reconnection support
 */
class SseStream
{
    protected $dataProvider = null; // callable
    protected mixed $subscription = null;
    protected int $heartbeatInterval;
    protected int $timeout;
    protected int $pollInterval;
    protected ?string $lastEventId = null;
    protected bool $closed = false;

    /**
     * @param callable|null $dataProvider Function that returns array of SseEvent objects
     * @param mixed|null $subscription PubSub subscription object
     * @param int $heartbeatInterval Seconds between heartbeats (default 15)
     * @param int $timeout Connection timeout in seconds (default 300)
     * @param int $pollInterval Poll interval in seconds (default 1)
     */
    public function __construct(
        ?callable $dataProvider = null,
        mixed $subscription = null,
        int $heartbeatInterval = 15,
        int $timeout = 300,
        int $pollInterval = 1
    ) {
        // Validate single source requirement
        if ($dataProvider !== null && $subscription !== null) {
            throw new \InvalidArgumentException(
                'SseStream accepts either dataProvider OR subscription, not both. ' .
                'Use dataProvider for polling mode or subscription for pubsub mode.'
            );
        }

        if ($dataProvider === null && $subscription === null) {
            throw new \InvalidArgumentException(
                'SseStream requires either dataProvider or subscription. ' .
                'Provide a data source for the stream.'
            );
        }

        $this->dataProvider = $dataProvider;
        $this->subscription = $subscription;
        $this->heartbeatInterval = $heartbeatInterval;
        $this->timeout = $timeout;
        $this->pollInterval = $pollInterval;
    }

    /**
     * Set Last-Event-ID for reconnection resume
     */
    public function setLastEventId(?string $lastEventId): void
    {
        $this->lastEventId = $lastEventId;
    }

    /**
     * Get Last-Event-ID
     */
    public function getLastEventId(): ?string
    {
        return $this->lastEventId;
    }

    /**
     * Close the stream
     */
    public function close(): void
    {
        $this->closed = true;
    }

    /**
     * Check if stream is closed
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Generator that yields SSE events
     */
    public function getIterator(): Generator
    {
        $startTime = time();

        // Polling mode
        if ($this->dataProvider !== null) {
            yield from $this->pollStream($startTime);
        }

        // PubSub mode
        if ($this->subscription !== null) {
            yield from $this->pubsubStream($startTime);
        }
    }

    /**
     * Polling mode stream generator
     */
    protected function pollStream(int $startTime): Generator
    {
        $lastHeartbeat = time();

        while (!$this->closed) {
            // Check timeout
            if (time() - $startTime >= $this->timeout) {
                yield SseEvent::comment('Connection timeout reached');
                break;
            }

            // Send heartbeat if needed
            if (time() - $lastHeartbeat >= $this->heartbeatInterval) {
                yield SseEvent::comment('heartbeat');
                $lastHeartbeat = time();
            }

            // Fetch data from provider
            try {
                $events = call_user_func($this->dataProvider, $this->lastEventId);

                if (is_array($events) && !empty($events)) {
                    foreach ($events as $event) {
                        if ($event instanceof SseEvent) {
                            if ($event->id !== null) {
                                $this->lastEventId = $event->id;
                            }
                            yield $event->format();
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Log error but continue stream
                yield SseEvent::comment('error: ' . $e->getMessage());
            }

            // Wait for next poll
            $this->safeSleep($this->pollInterval);
        }
    }

    /**
     * PubSub mode stream generator
     */
    protected function pubsubStream(int $startTime): Generator
    {
        $lastHeartbeat = time();

        while (!$this->closed) {
            // Check timeout
            if (time() - $startTime >= $this->timeout) {
                yield SseEvent::comment('Connection timeout reached');
                break;
            }

            // Send heartbeat if needed
            if (time() - $lastHeartbeat >= $this->heartbeatInterval) {
                yield SseEvent::comment('heartbeat');
                $lastHeartbeat = time();
            }

            // Block and wait for subscription message
            try {
                $message = $this->waitForSubscriptionMessage();

                if ($message !== null) {
                    $event = $message instanceof SseEvent
                        ? $message
                        : new SseEvent(data: $message);

                    if ($event->id !== null) {
                        $this->lastEventId = $event->id;
                    }

                    yield $event->format();
                }
            } catch (\Throwable $e) {
                yield SseEvent::comment('error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Wait for subscription message (blocking)
     * Override this method for custom pubsub implementation
     */
    protected function waitForSubscriptionMessage(): mixed
    {
        if (method_exists($this->subscription, 'receive')) {
            return $this->subscription->receive();
        }

        if (method_exists($this->subscription, 'pop')) {
            return $this->subscription->pop();
        }

        // Fallback: safe sleep if subscription doesn't support blocking
        $this->safeSleep(1);
        return null;
    }

    /**
     * Safe sleep that respects connection status
     */
    protected function safeSleep(int $seconds): void
    {
        $end = time() + $seconds;
        while (time() < $end && !$this->closed) {
            if (connection_aborted()) {
                $this->closed = true;
                break;
            }
            usleep(100000); // 100ms intervals
        }
    }
}
