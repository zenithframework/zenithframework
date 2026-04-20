<?php

declare(strict_types=1);

namespace Tests;

use Zenith\Http\SseEvent;
use Zenith\Http\SseStream;
use Zenith\Http\StreamingResponse;

/**
 * SSE (Server-Sent Events) Test Suite
 * 
 * Tests all SSE components for correctness
 */
class SseTest
{
    public function test_sse_event_format(): bool
    {
        $event = new SseEvent(
            data: ['message' => 'Hello World'],
            event: 'test',
            id: '1',
            retry: 3000
        );

        $formatted = $event->format();
        
        return str_contains($formatted, 'id: 1')
            && str_contains($formatted, 'event: test')
            && str_contains($formatted, 'retry: 3000')
            && str_contains($formatted, 'data: {"message":"Hello World"}');
    }

    public function test_sse_event_data_array(): bool
    {
        $event = new SseEvent(data: ['key' => 'value']);
        $formatted = $event->format();
        
        return str_contains($formatted, '{"key":"value"}');
    }

    public function test_sse_event_data_string(): bool
    {
        $event = new SseEvent(data: 'plain text');
        $formatted = $event->format();
        
        return str_contains($formatted, 'data: plain text');
    }

    public function test_sse_event_multiline_data(): bool
    {
        $event = new SseEvent(data: "line1\nline2");
        $formatted = $event->format();
        
        return str_contains($formatted, "data: line1\ndata: line2");
    }

    public function test_sse_event_id_only(): bool
    {
        $event = new SseEvent(data: 'test', id: '123');
        $formatted = $event->format();
        
        return str_contains($formatted, 'id: 123')
            && str_contains($formatted, 'data: test');
    }

    public function test_sse_event_no_optional_params(): bool
    {
        $event = new SseEvent(data: 'minimal');
        $formatted = $event->format();
        
        return str_contains($formatted, 'data: minimal')
            && !str_contains($formatted, 'id:')
            && !str_contains($formatted, 'event:')
            && !str_contains($formatted, 'retry:');
    }

    public function test_sse_comment(): bool
    {
        $comment = SseEvent::comment('heartbeat');
        
        return $comment === ": heartbeat\n\n";
    }

    public function test_sse_comment_custom(): bool
    {
        $comment = SseEvent::comment('custom comment');
        
        return str_contains($comment, ': custom comment');
    }

    public function test_sse_stream_data_provider(): bool
    {
        $stream = new SseStream(
            dataProvider: fn() => [
                new SseEvent(data: 'test1'),
                new SseEvent(data: 'test2'),
            ]
        );

        $events = iterator_to_array($stream->getIterator());
        
        return count($events) >= 2
            && str_contains($events[0], 'data: test1')
            && str_contains($events[1], 'data: test2');
    }

    public function test_sse_stream_last_event_id(): bool
    {
        $stream = new SseStream(
            dataProvider: function (?string $lastId) {
                return [new SseEvent(data: "lastId: $lastId", id: '1')];
            }
        );

        $stream->setLastEventId('previous-id');
        $events = iterator_to_array($stream->getIterator());
        
        return str_contains($events[0], 'lastId: previous-id');
    }

    public function test_sse_stream_close(): bool
    {
        $stream = new SseStream(
            dataProvider: fn() => [new SseEvent(data: 'test')]
        );

        $stream->close();
        
        return $stream->isClosed() === true;
    }

    public function test_streaming_response_headers(): bool
    {
        $stream = new SseStream(
            dataProvider: fn() => [new SseEvent(data: 'test')]
        );

        $response = new StreamingResponse($stream);
        $headers = $response->getHeaders();
        
        return $headers['Content-Type'] === 'text/event-stream'
            && $headers['Cache-Control'] === 'no-cache, no-store, must-revalidate'
            && $headers['X-Accel-Buffering'] === 'no';
    }

    public function test_streaming_response_from_data_provider(): bool
    {
        $response = StreamingResponse::fromDataProvider(
            dataProvider: fn() => [new SseEvent(data: 'test')],
            pollInterval: 1,
            heartbeatInterval: 15,
            timeout: 300
        );

        return $response instanceof StreamingResponse
            && $response->getStream() instanceof SseStream;
    }

    public function test_sse_stream_requires_source(): bool
    {
        try {
            new SseStream();
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'requires either dataProvider or subscription');
        }
    }

    public function test_sse_stream_rejects_both_sources(): bool
    {
        try {
            new SseStream(
                dataProvider: fn() => [],
                subscription: new \stdClass()
            );
            return false;
        } catch (\InvalidArgumentException $e) {
            return str_contains($e->getMessage(), 'accepts either dataProvider OR subscription');
        }
    }

    public function test_sse_event_json_encoding_error(): bool
    {
        // Test with invalid UTF-8 data that should trigger JSON error
        try {
            $invalidData = "\xB1\x31";
            $event = new SseEvent(data: $invalidData);
            $event->format();
            return false;
        } catch (\JsonException $e) {
            return true;
        } catch (\Throwable $e) {
            return true; // Any exception is acceptable for invalid data
        }
    }

    public function test_sse_heartbeat_in_poll_stream(): bool
    {
        $stream = new SseStream(
            dataProvider: fn() => [],
            heartbeatInterval: 1
        );

        // Give it time to generate heartbeat
        usleep(100000); // 100ms
        
        $events = [];
        foreach ($stream->getIterator() as $chunk) {
            $events[] = $chunk;
            if (count($events) >= 1) {
                $stream->close();
                break;
            }
        }

        return count($events) > 0;
    }
}
