<?php

declare(strict_types=1);

namespace Zen\Server;

use Zen\Http\Request;
use Zen\Http\Response;

class Server
{
    protected string $driver = 'fpm';
    protected array $config = [];
    protected bool $running = false;

    public function __construct()
    {
        $this->config = config('server') ?? [
            'driver' => 'fpm',
            'host' => '0.0.0.0',
            'port' => 8080,
            'workers' => 4,
        ];
        $this->driver = $this->config['driver'] ?? 'fpm';
    }

    public function start(): void
    {
        $this->running = true;

        match ($this->driver) {
            'swoole' => $this->startSwoole(),
            'workerman' => $this->startWorkerman(),
            'roadrunner' => $this->startRoadRunner(),
            default => $this->startFpm(),
        };
    }

    protected function startFpm(): void
    {
        echo "Running in FPM mode (standard PHP)\n";
        echo "Use 'php zen serve' to start the built-in server\n";
    }

    protected function startSwoole(): void
    {
        $swooleClass = 'Swoole\Http\Server';
        if (!class_exists($swooleClass)) {
            echo "Swoole extension not installed. Running in FPM mode.\n";
            $this->startFpm();
            return;
        }

        $server = new $swooleClass(
            $this->config['host'] ?? '0.0.0.0',
            $this->config['port'] ?? 8080
        );

        $server->set([
            'worker_num' => $this->config['workers'] ?? 4,
            'daemonize' => $this->config['daemonize'] ?? false,
            'max_request' => $this->config['max_request'] ?? 10000,
            'memory_limit' => $this->config['memory_limit'] ?? '128M',
        ]);

        $server->on('request', function ($request, $response) {
            $this->handleSwooleRequest($request, $response);
        });

        echo "Swoole server started on {$this->config['host']}:{$this->config['port']}\n";
        $server->start();
    }

    protected function handleSwooleRequest($request, $response): void
    {
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => $request->server['request_method'] ?? 'GET',
            'REQUEST_URI' => $request->server['request_uri'] ?? '/',
            'QUERY_STRING' => $request->server['query_string'] ?? '',
            'REMOTE_ADDR' => $request->server['remote_addr'] ?? '127.0.0.1',
            'HTTP_HOST' => $request->server['http_host'] ?? 'localhost',
            'HTTP_USER_AGENT' => $request->header['user_agent'] ?? '',
        ]);

        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];

        try {
            $requestObj = Request::capture();
            $app = app();
            $router = $app->make(\Zen\Routing\Router::class);
            
            $route = $router->match($requestObj);
            
            if ($route === null) {
                $response->status(404);
                $response->end('Not Found');
                return;
            }

            $controller = $route->getHandler()[0];
            $method = $route->getHandler()[1] ?? '__invoke';
            
            if (is_string($controller)) {
                $controller = new $controller();
            }

            $result = $controller->$method($requestObj, ...$route->getParameters());
            
            if ($result instanceof Response) {
                $response->status($result->getStatusCode());
                foreach ($result->getHeaders() as $key => $value) {
                    $response->header($key, $value);
                }
                $response->end($result->getContent());
            } else {
                $response->end((string) $result);
            }
        } catch (\Throwable $e) {
            $response->status(500);
            $response->end('Internal Server Error: ' . $e->getMessage());
        }
    }

    protected function startWorkerman(): void
    {
        $workermanClass = 'Workerman\Worker';
        if (!class_exists($workermanClass)) {
            echo "Workerman not installed. Run: composer require workerman/workerman\n";
            $this->startFpm();
            return;
        }

        $worker = new $workermanClass("tcp://{$this->config['host']}:{$this->config['port']}");
        
        $worker->onMessage = function ($connection, $data) {
            $connection->send("Zen Framework Workerman\n");
        };

        echo "Workerman server started on {$this->config['host']}:{$this->config['port']}\n";
        $workermanClass::runAll();
    }

    protected function startRoadRunner(): void
    {
        echo "RoadRunner requires separate installation.\n";
        echo "See: https://roadrunner.dev/docs/php-sdk\n";
        $this->startFpm();
    }

    public function stop(): void
    {
        $this->running = false;
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}

class WorkerPool
{
    protected int $workerCount;
    protected array $workers = [];
    protected int $currentWorker = 0;
    protected bool $initialized = false;

    public function __construct(int $workerCount = 4)
    {
        $this->workerCount = $workerCount;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        for ($i = 0; $i < $this->workerCount; $i++) {
            $this->workers[$i] = [
                'id' => $i,
                'status' => 'idle',
                'requests' => 0,
                'memory' => 0,
                'cpu' => 0,
            ];
        }

        $this->initialized = true;
    }

    public function getWorker(): int
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $this->currentWorker = ($this->currentWorker + 1) % $this->workerCount;
        return $this->currentWorker;
    }

    public function recordRequest(int $workerId): void
    {
        if (isset($this->workers[$workerId])) {
            $this->workers[$workerId]['requests']++;
            $this->workers[$workerId]['status'] = 'busy';
        }
    }

    public function releaseWorker(int $workerId): void
    {
        if (isset($this->workers[$workerId])) {
            $this->workers[$workerId]['status'] = 'idle';
        }
    }

    public function getStats(): array
    {
        return [
            'total_workers' => $this->workerCount,
            'busy_workers' => count(array_filter($this->workers, fn($w) => $w['status'] === 'busy')),
            'idle_workers' => count(array_filter($this->workers, fn($w) => $w['status'] === 'idle')),
            'total_requests' => array_sum(array_column($this->workers, 'requests')),
        ];
    }

    public function getLeastLoadedWorker(): int
    {
        $minRequests = PHP_INT_MAX;
        $workerId = 0;

        foreach ($this->workers as $id => $worker) {
            if ($worker['requests'] < $minRequests) {
                $minRequests = $worker['requests'];
                $workerId = $id;
            }
        }

        return $workerId;
    }
}

class SharedMemory
{
    protected $shmId = null;
    protected int $size = 1048576;

    public function __construct(string $key = 'zen_shm', int $size = 1048576)
    {
        $this->size = $size;
        
        if (function_exists('shmop_open')) {
            $this->shmId = @shmop_open($this->getKey($key), 'c', 0644, $size);
            
            if ($this->shmId === false) {
                $this->shmId = shmop_open($this->getKey($key), 'a', 0, 0);
            }
        }
    }

    protected function getKey(string $key): int
    {
        return ftok(__FILE__, $key[0] ?? 'Z') ?: hexdec(substr(md5($key), 0, 8));
    }

    public function write(string $data): bool
    {
        if ($this->shmId === null) {
            return false;
        }

        $size = shmop_size($this->shmId);
        if (strlen($data) > $size) {
            return false;
        }

        return shmop_write($this->shmId, $data, 0) > 0;
    }

    public function read(): ?string
    {
        if ($this->shmId === null) {
            return null;
        }

        $size = shmop_size($this->shmId);
        return shmop_read($this->shmId, 0, $size) ?: null;
    }

    public function delete(): bool
    {
        if ($this->shmId !== null) {
            return shmop_delete($this->shmId);
        }
        return false;
    }

    public function exists(): bool
    {
        return $this->shmId !== null && shmop_size($this->shmId) > 0;
    }
}

class InterProcessCache
{
    protected array $cache = [];
    protected int $ttl = 3600;
    protected string $prefix = 'zen_ipc_';

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;
    }

    public function get(string $key): mixed
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $item = $this->cache[$key];

        if ($item['expires_at'] < time()) {
            unset($this->cache[$key]);
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->ttl;

        $this->cache[$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time(),
        ];
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key) ?: 0;
        $new = $current + $value;
        $this->set($key, $new);
        return $new;
    }

    public function decrement(string $key, int $value = 1): int
    {
        return $this->increment($key, -$value);
    }

    public function cleanup(): void
    {
        $now = time();
        
        foreach ($this->cache as $key => $item) {
            if ($item['expires_at'] < $now) {
                unset($this->cache[$key]);
            }
        }
    }
}