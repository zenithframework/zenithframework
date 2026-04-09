<?php

declare(strict_types=1);

namespace Zenith\Http;

/**
 * SSE Event representation
 * 
 * Represents a single Server-Sent Event with automatic protocol formatting
 */
class SseEvent
{
    public function __construct(
        public mixed $data,
        public ?string $event = null,
        public ?string $id = null,
        public ?int $retry = null
    ) {
    }

    /**
     * Format event to SSE protocol string
     */
    public function format(): string
    {
        $output = '';

        // Add event ID (for reconnection)
        if ($this->id !== null) {
            $output .= "id: {$this->id}\n";
        }

        // Add event name
        if ($this->event !== null) {
            $output .= "event: {$this->event}\n";
        }

        // Add retry interval
        if ($this->retry !== null) {
            $output .= "retry: {$this->retry}\n";
        }

        // Add data (required)
        $data = is_array($this->data) ? json_encode($this->data, JSON_THROW_ON_ERROR) : (string) $this->data;
        $output .= 'data: ' . str_replace("\n", "\ndata: ", $data) . "\n";

        // Empty line to separate events
        $output .= "\n";

        return $output;
    }

    /**
     * Create a comment (heartbeat)
     */
    public static function comment(string $comment): string
    {
        return ": {$comment}\n\n";
    }
}
