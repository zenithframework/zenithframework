<?php

declare(strict_types=1);

namespace Zenith\Http;

use Zenith\Http\Response;
use Zenith\Http\SseStream;

/**
 * Streaming Response for SSE and other streaming content
 * 
 * Extends Response to support HTTP streaming with automatic
 * header setup and output buffer management
 */
class StreamingResponse extends Response
{
    protected SseStream $stream;
    protected int $flushInterval;

    /**
     * @param SseStream $stream SSE stream to send
     * @param int $status HTTP status code
     * @param int $flushInterval Flush interval in milliseconds (default 100)
     */
    public function __construct(
        SseStream $stream,
        int $status = 200,
        int $flushInterval = 100
    ) {
        $this->stream = $stream;
        $this->flushInterval = $flushInterval;
        parent::__construct('', $status, $this->getStreamingHeaders());
    }

    /**
     * Get streaming-specific headers
     */
    protected function getStreamingHeaders(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Connection' => 'keep-alive',
        ];
    }

    /**
     * Send the streaming response
     */
    public function send(): void
    {
        // Set HTTP status
        http_response_code($this->status);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Extract Last-Event-ID if available
        $lastEventId = $_SERVER['HTTP_LAST_EVENT_ID'] ?? null;
        if ($lastEventId) {
            $this->stream->setLastEventId($lastEventId);
        }

        // Flush output buffers
        if (ob_get_level()) {
            ob_end_flush();
        }

        // Register shutdown function for cleanup
        register_shutdown_function(function () {
            $this->stream->close();
        });

        // Stream events
        foreach ($this->stream->getIterator() as $chunk) {
            echo $chunk;

            // Flush output
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Check connection status
            if (connection_aborted()) {
                $this->stream->close();
                break;
            }

            // Small delay to prevent CPU hogging
            usleep($this->flushInterval * 1000);
        }

        // Final cleanup
        $this->stream->close();
    }

    /**
     * Get the stream
     */
    public function getStream(): SseStream
    {
        return $this->stream;
    }

    /**
     * Create SSE streaming response from data provider
     */
    public static function fromDataProvider(
        callable $dataProvider,
        int $pollInterval = 1,
        int $heartbeatInterval = 15,
        int $timeout = 300
    ): static {
        $stream = new SseStream(
            dataProvider: $dataProvider,
            pollInterval: $pollInterval,
            heartbeatInterval: $heartbeatInterval,
            timeout: $timeout
        );

        return new static($stream);
    }

    /**
     * Create SSE streaming response from subscription
     */
    public static function fromSubscription(
        mixed $subscription,
        int $heartbeatInterval = 15,
        int $timeout = 300
    ): static {
        $stream = new SseStream(
            subscription: $subscription,
            heartbeatInterval: $heartbeatInterval,
            timeout: $timeout
        );

        return new static($stream);
    }
}
