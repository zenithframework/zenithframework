<?php

declare(strict_types=1);

namespace Zenith\WebSocket;

class WebSocketServer
{
    protected $socket = null;
    protected int $port = 8080;
    protected array $clients = [];
    protected array $rooms = [];
    protected bool $running = false;

    public function __construct(int $port = 8080)
    {
        $this->port = $port;
    }

    public function start(): void
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, '0.0.0.0', $this->port);
        socket_listen($this->socket);

        $this->running = true;
        echo "WebSocket server started on port {$this->port}\n";

        while ($this->running) {
            $client = socket_accept($this->socket);
            
            if ($client !== false) {
                $this->handleConnection($client);
            }
        }
    }

    public function stop(): void
    {
        $this->running = false;
        
        if ($this->socket !== null) {
            socket_close($this->socket);
        }
    }

    protected function handleConnection($client): void
    {
        $headers = socket_read($client, 1024);
        
        if ($this->isWebSocketUpgrade($headers)) {
            $this->performHandshake($client, $headers);
            
            $id = uniqid('client_');
            $this->clients[$id] = $client;
            
            $this->send($client, json_encode([
                'type' => 'connected',
                'client_id' => $id,
            ]));

            $this->readLoop($client, $id);
        }
    }

    protected function isWebSocketUpgrade(string $headers): bool
    {
        return str_contains($headers, 'Upgrade: websocket');
    }

    protected function performHandshake($client, string $headers): void
    {
        preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $headers, $matches);
        $key = $matches[1] ?? '';
        
        $accept = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        
        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
        
        socket_write($client, $response, strlen($response));
    }

    protected function readLoop($client, string $id): void
    {
        while (true) {
            $data = @socket_read($client, 4096);
            
            if ($data === false || $data === '') {
                break;
            }

            $message = $this->decode($data);
            
            if ($message !== null) {
                $this->handleMessage($id, $message);
            }
        }

        unset($this->clients[$id]);
    }

    protected function handleMessage(string $clientId, array $message): void
    {
        $type = $message['type'] ?? 'unknown';
        $data = $message['data'] ?? [];

        switch ($type) {
            case 'join_room':
                $this->joinRoom($clientId, $data['room'] ?? 'default');
                break;
                
            case 'leave_room':
                $this->leaveRoom($clientId, $data['room'] ?? 'default');
                break;
                
            case 'broadcast':
                $this->broadcast($clientId, $data['message'] ?? '', $data['room'] ?? 'default');
                break;
                
            case 'ping':
                $this->send($this->clients[$clientId], json_encode(['type' => 'pong']));
                break;
        }
    }

    public function joinRoom(string $clientId, string $room): void
    {
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }
        
        $this->rooms[$room][] = $clientId;
    }

    public function leaveRoom(string $clientId, string $room): void
    {
        if (isset($this->rooms[$room])) {
            $this->rooms[$room] = array_filter($this->rooms[$room], fn($id) => $id !== $clientId);
        }
    }

    public function broadcast(string $fromClient, string $message, string $room = 'default'): void
    {
        if (!isset($this->rooms[$room])) {
            return;
        }

        foreach ($this->rooms[$room] as $clientId) {
            if ($clientId !== $fromClient && isset($this->clients[$clientId])) {
                $this->send($this->clients[$clientId], json_encode([
                    'type' => 'message',
                    'from' => $fromClient,
                    'message' => $message,
                ]));
            }
        }
    }

    protected function send($client, string $message): void
    {
        $encoded = $this->encode($message);
        socket_write($client, $encoded, strlen($encoded));
    }

    protected function decode(string $data): ?array
    {
        $length = ord($data[1]) & 127;
        
        if ($length === 126) {
            $mask = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($length === 127) {
            $mask = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            $mask = substr($data, 2, 4);
            $payload = substr($data, 6);
        }

        $decoded = '';
        
        for ($i = 0; $i < strlen($payload); $i++) {
            $decoded .= $payload[$i] ^ $mask[$i % 4];
        }

        return json_decode($decoded, true);
    }

    protected function encode(string $message): string
    {
        $length = strlen($message);
        
        $data = "\x81";
        
        if ($length <= 125) {
            $data .= chr($length);
        } elseif ($length <= 65535) {
            $data .= "\x7E" . pack('n', $length);
        } else {
            $data .= "\x7F" . pack('J', $length);
        }
        
        return $data . $message;
    }

    public function getStats(): array
    {
        return [
            'connected_clients' => count($this->clients),
            'rooms' => count($this->rooms),
            'total_clients_in_rooms' => array_sum(array_map('count', $this->rooms)),
        ];
    }
}

class SSEHandler
{
    protected array $clients = [];

    public function start(callable $generator): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $this->send(['event' => 'connected', 'data' => time()]);

        while (true) {
            $data = $generator();
            
            if ($data !== null) {
                $this->send(['event' => 'message', 'data' => $data]);
            }

            sleep(1);
        }
    }

    protected function send(array $event): void
    {
        echo "event: " . ($event['event'] ?? 'message') . "\n";
        echo "data: " . json_encode($event['data']) . "\n\n";
        flush();
    }
}

class ConnectionManager
{
    protected array $connections = [];
    protected int $maxConnections = 10000;

    public function add(string $id, array $metadata = []): bool
    {
        if (count($this->connections) >= $this->maxConnections) {
            return false;
        }

        $this->connections[$id] = array_merge($metadata, [
            'connected_at' => time(),
            'last_activity' => time(),
        ]);

        return true;
    }

    public function remove(string $id): void
    {
        unset($this->connections[$id]);
    }

    public function updateActivity(string $id): void
    {
        if (isset($this->connections[$id])) {
            $this->connections[$id]['last_activity'] = time();
        }
    }

    public function get(string $id): ?array
    {
        return $this->connections[$id] ?? null;
    }

    public function getAll(): array
    {
        return $this->connections;
    }

    public function cleanup(int $timeout = 300): int
    {
        $now = time();
        $cleaned = 0;

        foreach ($this->connections as $id => $conn) {
            if (($now - $conn['last_activity']) > $timeout) {
                unset($this->connections[$id]);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    public function getStats(): array
    {
        return [
            'total' => count($this->connections),
            'max' => $this->maxConnections,
        ];
    }
}