<?php

declare(strict_types=1);

namespace Zenith\Database;

use PDO;
use PDOException;

class FastConnectionPool
{
    protected array $pool = [];
    protected array $waiting = [];
    protected int $minConnections = 5;
    protected int $maxConnections = 50;
    protected int $timeout = 5;
    protected string $driver = 'sqlite';
    protected array $config = [];
    protected bool $initialized = false;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->driver = $config['driver'] ?? 'sqlite';
        $this->minConnections = $config['min_connections'] ?? 5;
        $this->maxConnections = $config['max_connections'] ?? 50;
        $this->timeout = $config['timeout'] ?? 5;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->pool[] = $this->createConnection();
        }

        $this->initialized = true;
    }

    protected function createConnection(): PDO
    {
        $dsn = $this->buildDsn();
        $pdo = new PDO($dsn, $this->config['username'] ?? '', $this->config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    }

    protected function buildDsn(): string
    {
        $config = $this->config;

        return match ($this->driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 3306,
                $config['database'] ?? 'zen'
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 5432,
                $config['database'] ?? 'zen'
            ),
            default => 'sqlite:' . ($config['database'] ?? dirname(__DIR__, 2) . '/database/database.sqlite'),
        };
    }

    public function getConnection(): PDO
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (!empty($this->pool)) {
            return array_pop($this->pool);
        }

        if (count($this->pool) < $this->maxConnections) {
            return $this->createConnection();
        }

        $start = time();
        while (empty($this->pool)) {
            if ((time() - $start) >= $this->timeout) {
                throw new PDOException('Connection pool timeout');
            }
            usleep(10000);
        }

        return array_pop($this->pool);
    }

    public function releaseConnection(\PDO $connection): void
    {
        if (count($this->pool) < $this->maxConnections) {
            $this->pool[] = $connection;
        }
    }

    public function execute(callable $callback): mixed
    {
        $connection = $this->getConnection();

        try {
            return $callback($connection);
        } finally {
            $this->releaseConnection($connection);
        }
    }

    public function getStats(): array
    {
        return [
            'active' => count($this->pool),
            'max' => $this->maxConnections,
            'min' => $this->minConnections,
            'waiting' => count($this->waiting),
        ];
    }
}

class QueryPipeline
{
    protected FastConnectionPool $pool;
    protected array $queries = [];
    protected bool $transaction = false;

    public function __construct(FastConnectionPool $pool)
    {
        $this->pool = $pool;
    }

    public function add(string $sql, array $bindings = []): self
    {
        $this->queries[] = ['sql' => $sql, 'bindings' => $bindings];
        return $this;
    }

    public function execute(): array
    {
        return $this->pool->execute(function ($pdo) {
            $results = [];

            foreach ($this->queries as $query) {
                $stmt = $pdo->prepare($query['sql']);
                $stmt->execute($query['bindings']);
                $results[] = $stmt->fetchAll();
            }

            return $results;
        });
    }

    public function executeParallel(): array
    {
        $results = [];
        
        foreach ($this->queries as $query) {
            $results[] = $this->pool->execute(function ($pdo) use ($query) {
                $stmt = $pdo->prepare($query['sql']);
                $stmt->execute($query['bindings']);
                return $stmt->fetchAll();
            });
        }

        return $results;
    }

    public function clear(): void
    {
        $this->queries = [];
    }
}

class PreparedStatementCache
{
    protected array $cache = [];
    protected int $maxSize = 500;
    protected array $hits = [];
    protected array $misses = [];

    public function get(PDO $pdo, string $sql): ?\PDOStatement
    {
        $key = md5($sql);

        if (isset($this->cache[$key])) {
            $this->hits[$key] = ($this->hits[$key] ?? 0) + 1;
            return $this->cache[$key];
        }

        $this->misses[$key] = ($this->misses[$key] ?? 0) + 1;

        if (count($this->cache) >= $this->maxSize) {
            $leastUsed = array_keys($this->hits, min($this->hits));
            if (isset($leastUsed[0])) {
                unset($this->cache[$leastUsed[0]], $this->hits[$leastUsed[0]]);
            }
        }

        $stmt = $pdo->prepare($sql);
        $this->cache[$key] = $stmt;

        return $stmt;
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->hits = [];
        $this->misses = [];
    }

    public function getStats(): array
    {
        $totalHits = array_sum($this->hits);
        $totalMisses = array_sum($this->misses);
        $total = $totalHits + $totalMisses;

        return [
            'cached_statements' => count($this->cache),
            'hits' => $totalHits,
            'misses' => $totalMisses,
            'hit_rate' => $total > 0 ? round(($totalHits / $total) * 100, 2) : 0 . '%',
        ];
    }
}

class QueryOptimizer
{
    public static function optimize(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', $sql);
        
        $sql = trim($sql);

        return $sql;
    }

    public static function explain(PDO $pdo, string $sql): array
    {
        $stmt = $pdo->prepare("EXPLAIN QUERY PLAN {$sql}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function suggestIndexes(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name='{$table}'");
        $existingIndexes = $stmt->fetchAll();

        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll();

        $suggestions = [];
        
        foreach ($columns as $column) {
            if ($column['type'] === 'INTEGER' || $column['type'] === 'VARCHAR(255)') {
                $suggestions[] = "CREATE INDEX IF NOT EXISTS idx_{$table}_{$column['name']} ON {$table}({$column['name']})";
            }
        }

        return $suggestions;
    }
}