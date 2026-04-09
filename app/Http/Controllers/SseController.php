<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\SseEvent;
use Zenith\Http\SseStream;
use Zenith\Http\StreamingResponse;

/**
 * Example SSE Controller
 * 
 * Demonstrates Server-Sent Events implementation in Zenith Framework
 */
class SseController
{
    /**
     * Simple SSE stream endpoint
     */
    #[\Zenith\Routing\Attributes\Get('/sse/time')]
    public function timeStream(): StreamingResponse
    {
        return StreamingResponse::fromDataProvider(
            dataProvider: function (?string $lastEventId): array {
                $eventId = uniqid('time_');
                
                return [
                    new SseEvent(
                        data: ['time' => date('Y-m-d H:i:s'), 'timestamp' => time()],
                        event: 'time-update',
                        id: $eventId
                    ),
                ];
            },
            pollInterval: 1,
            heartbeatInterval: 15,
            timeout: 300
        );
    }

    /**
     * Notification stream endpoint
     */
    #[\Zenith\Routing\Attributes\Get('/sse/notifications/{userId}')]
    public function notifications(Request $request, int $userId): StreamingResponse
    {
        $lastEventId = $request->header('Last-Event-ID');

        return new StreamingResponse(
            new SseStream(
                dataProvider: function (?string $lastId) use ($userId): array {
                    // Fetch notifications since last event ID
                    $notifications = $this->getNewNotifications($userId, $lastId);

                    return array_map(
                        fn($notification) => new SseEvent(
                            data: [
                                'id' => $notification['id'],
                                'message' => $notification['message'],
                                'type' => $notification['type'],
                            ],
                            event: $notification['type'],
                            id: (string) $notification['id']
                        ),
                        $notifications
                    );
                },
                pollInterval: 2,
                heartbeatInterval: 30,
                timeout: 600
            )
        );
    }

    /**
     * Progress indicator endpoint
     */
    #[\Zenith\Routing\Attributes\Get('/sse/progress/{taskId}')]
    public function progress(Request $request, string $taskId): StreamingResponse
    {
        return StreamingResponse::fromDataProvider(
            dataProvider: function (?string $lastEventId) use ($taskId): array {
                $progress = $this->getTaskProgress($taskId, $lastEventId);

                $events = [];

                if ($progress !== null) {
                    $events[] = new SseEvent(
                        data: [
                            'task_id' => $taskId,
                            'progress' => $progress['percentage'],
                            'status' => $progress['status'],
                            'message' => $progress['message'],
                        ],
                        event: 'progress',
                        id: (string) $progress['id']
                    );

                    // Send completion event if done
                    if ($progress['status'] === 'completed') {
                        $events[] = new SseEvent(
                            data: ['task_id' => $taskId, 'result' => $progress['result']],
                            event: 'completed',
                            id: (string) ($progress['id'] + 1)
                        );
                    }
                }

                return $events;
            },
            pollInterval: 1,
            heartbeatInterval: 10,
            timeout: 3600
        );
    }

    /**
     * Chat message stream endpoint
     */
    #[\Zenith\Routing\Attributes\Get('/sse/chat/{roomId}')]
    public function chatStream(Request $request, int $roomId): StreamingResponse
    {
        $lastEventId = $request->header('Last-Event-ID');

        return new StreamingResponse(
            new SseStream(
                dataProvider: function (?string $lastId) use ($roomId): array {
                    $messages = $this->getNewMessages($roomId, $lastId);

                    return array_map(
                        fn($msg) => new SseEvent(
                            data: [
                                'id' => $msg['id'],
                                'user' => $msg['user'],
                                'message' => $msg['message'],
                                'created_at' => $msg['created_at'],
                            ],
                            event: 'message',
                            id: (string) $msg['id']
                        ),
                        $messages
                    );
                },
                pollInterval: 1,
                heartbeatInterval: 15,
                timeout: 1800
            )
        );
    }

    // Helper methods (replace with actual database queries)

    protected function getNewNotifications(int $userId, ?string $lastId): array
    {
        // Example: Replace with actual database query
        // $query = Notification::where('user_id', $userId);
        // if ($lastId) {
        //     $query->where('id', '>', (int) $lastId);
        // }
        // return $query->orderBy('id')->limit(10)->get()->toArray();

        return [];
    }

    protected function getTaskProgress(string $taskId, ?string $lastId): ?array
    {
        // Example: Replace with actual cache/database query
        // return Cache::get("task:{$taskId}:progress");

        return null;
    }

    protected function getNewMessages(int $roomId, ?string $lastId): array
    {
        // Example: Replace with actual database query
        // $query = Message::where('room_id', $roomId);
        // if ($lastId) {
        //     $query->where('id', '>', (int) $lastId);
        // }
        // return $query->with('user')->orderBy('id')->limit(50)->get()->toArray();

        return [];
    }
}
