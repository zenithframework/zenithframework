<?php

declare(strict_types=1);

namespace Zenith\Cluster;

class LoadBalancer
{
    protected array $nodes = [];
    protected string $strategy = 'round_robin';
    protected int $currentIndex = 0;
    protected array $nodeStats = [];

    public function __construct(array $nodes = [], string $strategy = 'round_robin')
    {
        $this->nodes = $nodes;
        $this->strategy = $strategy;
    }

    public function addNode(string $host, int $port, int $weight = 1): void
    {
        $this->nodes[] = [
            'host' => $host,
            'port' => $port,
            'weight' => $weight,
            'healthy' => true,
        ];
    }

    public function removeNode(string $host, int $port): void
    {
        $this->nodes = array_filter($this->nodes, fn($n) => 
            !($n['host'] === $host && $n['port'] === $port)
        );
    }

    public function getNode(): ?array
    {
        $healthyNodes = array_filter($this->nodes, fn($n) => $n['healthy']);

        if (empty($healthyNodes)) {
            return null;
        }

        return match ($this->strategy) {
            'round_robin' => $this->roundRobin($healthyNodes),
            'least_connections' => $this->leastConnections($healthyNodes),
            'weighted' => $this->weighted($healthyNodes),
            'ip_hash' => $this->ipHash($healthyNodes),
            default => $this->roundRobin($healthyNodes),
        };
    }

    protected function roundRobin(array $nodes): array
    {
        $node = $nodes[$this->currentIndex % count($nodes)];
        $this->currentIndex++;
        return $node;
    }

    protected function leastConnections(array $nodes): array
    {
        $minConnections = PHP_INT_MAX;
        $selected = null;

        foreach ($nodes as $node) {
            $connections = $this->getNodeConnections($node);
            
            if ($connections < $minConnections) {
                $minConnections = $connections;
                $selected = $node;
            }
        }

        return $selected ?? $nodes[0];
    }

    protected function weighted(array $nodes): array
    {
        $totalWeight = array_sum(array_column($nodes, 'weight'));
        $random = rand(1, $totalWeight);
        
        $current = 0;
        
        foreach ($nodes as $node) {
            $current += $node['weight'];
            
            if ($current >= $random) {
                return $node;
            }
        }

        return $nodes[0];
    }

    protected function ipHash(array $nodes): array
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $hash = crc32($ip);
        $index = $hash % count($nodes);
        
        return $nodes[$index];
    }

    protected function getNodeConnections(array $node): int
    {
        $key = $node['host'] . ':' . $node['port'];
        return $this->nodeStats[$key]['connections'] ?? 0;
    }

    public function recordConnection(array $node): void
    {
        $key = $node['host'] . ':' . $node['port'];
        
        if (!isset($this->nodeStats[$key])) {
            $this->nodeStats[$key] = ['connections' => 0, 'requests' => 0];
        }
        
        $this->nodeStats[$key]['connections']++;
        $this->nodeStats[$key]['requests']++;
    }

    public function releaseConnection(array $node): void
    {
        $key = $node['host'] . ':' . $node['port'];
        
        if (isset($this->nodeStats[$key])) {
            $this->nodeStats[$key]['connections'] = max(0, 
                $this->nodeStats[$key]['connections'] - 1
            );
        }
    }

    public function setNodeHealth(string $host, int $port, bool $healthy): void
    {
        foreach ($this->nodes as &$node) {
            if ($node['host'] === $host && $node['port'] === $port) {
                $node['healthy'] = $healthy;
            }
        }
    }

    public function getStats(): array
    {
        return [
            'total_nodes' => count($this->nodes),
            'healthy_nodes' => count(array_filter($this->nodes, fn($n) => $n['healthy'])),
            'strategy' => $this->strategy,
            'node_stats' => $this->nodeStats,
        ];
    }
}

class HealthChecker
{
    protected array $nodes = [];
    protected int $timeout = 2;
    protected int $interval = 30;

    public function __construct()
    {
        $this->interval = config('cluster.health_check_interval') ?? 30;
    }

    public function check(string $host, int $port): bool
    {
        $socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
        
        if ($socket === false) {
            return false;
        }

        fclose($socket);
        return true;
    }

    public function checkAll(): array
    {
        $results = [];
        
        foreach ($this->nodes as $node) {
            $results[$node['host'] . ':' . $node['port']] = $this->check(
                $node['host'], 
                $node['port']
            );
        }
        
        return $results;
    }

    public function registerNode(string $host, int $port): void
    {
        $this->nodes[] = ['host' => $host, 'port' => $port];
    }

    public function setTimeout(int $seconds): void
    {
        $this->timeout = $seconds;
    }
}

class FailoverManager
{
    protected array $primaryNodes = [];
    protected array $backupNodes = [];
    protected int $maxRetries = 3;

    public function __construct(array $config = [])
    {
        $this->maxRetries = $config['max_retries'] ?? 3;
    }

    public function setPrimaryNodes(array $nodes): void
    {
        $this->primaryNodes = $nodes;
    }

    public function setBackupNodes(array $nodes): void
    {
        $this->backupNodes = $nodes;
    }

    public function execute(callable $callback): mixed
    {
        foreach ($this->primaryNodes as $node) {
            try {
                return $callback($node);
            } catch (\Throwable $e) {
                continue;
            }
        }

        foreach ($this->backupNodes as $node) {
            try {
                return $callback($node);
            } catch (\Throwable $e) {
                continue;
            }
        }

        throw new \RuntimeException('All nodes failed');
    }

    public function withFailover(callable $callback, array $fallback = null): mixed
    {
        $attempts = 0;
        
        while ($attempts < $this->maxRetries) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $attempts++;
                
                if ($attempts >= $this->maxRetries && $fallback !== null) {
                    return $fallback();
                }
            }
        }

        throw new \RuntimeException("Failed after {$this->maxRetries} attempts");
    }
}

class ServiceDiscovery
{
    protected array $services = [];
    protected int $ttl = 60;

    public function __construct()
    {
        $this->ttl = config('cluster.service_ttl') ?? 60;
    }

    public function register(string $service, string $host, int $port, array $metadata = []): void
    {
        $this->services[$service][] = [
            'host' => $host,
            'port' => $port,
            'metadata' => $metadata,
            'registered_at' => time(),
            'expires_at' => time() + $this->ttl,
        ];
    }

    public function unregister(string $service, string $host, int $port): void
    {
        if (!isset($this->services[$service])) {
            return;
        }

        $this->services[$service] = array_filter(
            $this->services[$service],
            fn($s) => !($s['host'] === $host && $s['port'] === $port)
        );
    }

    public function discover(string $service): array
    {
        if (!isset($this->services[$service])) {
            return [];
        }

        $now = time();
        
        return array_filter($this->services[$service], fn($s) => $s['expires_at'] > $now);
    }

    public function refresh(string $service, string $host, int $port): void
    {
        $this->unregister($service, $host, $port);
        $this->register($service, $host, $port);
    }

    public function getAllServices(): array
    {
        return array_keys($this->services);
    }
}