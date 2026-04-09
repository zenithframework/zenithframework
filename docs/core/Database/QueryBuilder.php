<?php

declare(strict_types=1);

namespace Zenith\Database;

use PDO;
use PDOException;
use PDOStatement;

class QueryBuilder
{
    protected PDO $pdo;
    protected string $table = '';
    protected array $conditions = [];
    protected array $bindings = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $columns = ['*'];
    protected array $joins = [];
    protected array $groupBy = [];
    protected ?string $having = null;

    public function __construct()
    {
        $this->pdo = $this->getConnection();
    }

    protected function getConnection(): PDO
    {
        static $pdo = null;

        if ($pdo !== null) {
            return $pdo;
        }

        $driver = 'sqlite';
        // Project root is 3 levels up from core/Database/QueryBuilder.php
        $projectRoot = dirname(__DIR__, 3);
        $databasePath = $projectRoot . '/database/database.sqlite';

        $config = config('database');
        if ($config !== null && is_array($config)) {
            $driver = $config['default'] ?? 'sqlite';
            $connection = $config['connections'][$driver] ?? [];
            if (isset($connection['database'])) {
                $databasePath = $connection['database'];
            }
        }

        if ($driver === 'sqlite') {
            $dsn = 'sqlite:' . $databasePath;
            $pdo = new PDO($dsn);
        } elseif ($driver === 'mysql') {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $connection['host'] ?? '127.0.0.1',
                $connection['port'] ?? '3306',
                $connection['database'] ?? 'zen',
                $connection['charset'] ?? 'utf8mb4'
            );
            $pdo = new PDO($dsn, $connection['username'] ?? '', $connection['password'] ?? '');
        } elseif ($driver === 'pgsql') {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $connection['host'] ?? '127.0.0.1',
                $connection['port'] ?? '5432',
                $connection['database'] ?? 'zen'
            );
            $pdo = new PDO($dsn, $connection['username'] ?? '', $connection['password'] ?? '');
        } else {
            throw new \RuntimeException("Unsupported database driver: {$driver}");
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ['*']): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = 'where_' . count($this->bindings);
        $this->conditions[] = "{$column} {$operator} :{$placeholder}";
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        if (empty($values)) {
            $this->conditions[] = '1 = 0';
            return $this;
        }

        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = "wherein_{$column}_{$i}";
            $placeholders[] = ":{$placeholder}";
            $this->bindings[$placeholder] = $value;
        }

        $this->conditions[] = "{$column} IN (" . implode(', ', $placeholders) . ")";

        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->conditions[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->conditions[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = 'orwhere_' . count($this->bindings);
        $this->conditions[] = "OR {$column} {$operator} :{$placeholder}";
        $this->bindings[$placeholder] = $value;

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function groupBy(string $column): static
    {
        $this->groupBy[] = $column;
        return $this;
    }

    public function having(string $condition): static
    {
        $this->having = $condition;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): array|false
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? false;
    }

    public function find(int $id): array|false
    {
        return $this->where('id', $id)->first();
    }

    public function pluck(string $column): array
    {
        return array_column($this->get(), $column);
    }

    public function count(string $column = '*'): int
    {
        $sql = "SELECT COUNT({$column}) as aggregate FROM {$this->table}" . $this->buildConditionsSql();
        $stmt = $this->execute($sql, $this->bindings);
        return (int) $stmt->fetchColumn();
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function avg(string $column): mixed
    {
        return $this->aggregate('AVG', $column);
    }

    public function sum(string $column): mixed
    {
        return $this->aggregate('SUM', $column);
    }

    protected function aggregate(string $function, string $column): mixed
    {
        $sql = "SELECT {$function}({$column}) as aggregate FROM {$this->table}" . $this->buildConditionsSql();
        $stmt = $this->execute($sql, $this->bindings);
        return $stmt->fetchColumn();
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        if (!isset($data[0])) {
            $data = [$data];
        }

        $columns = array_keys($data[0]);
        $columnList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

        $placeholders = [];
        $bindings = [];

        foreach ($data as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $placeholder = "insert_{$i}_{$col}";
                $rowPlaceholders[] = ":{$placeholder}";
                $bindings[$placeholder] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES " . implode(', ', $placeholders);

        return $this->execute($sql, $bindings)->rowCount() > 0;
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $setParts = [];
        $bindings = $this->bindings;

        foreach ($data as $column => $value) {
            $placeholder = "update_{$column}";
            $setParts[] = "`{$column}` = :{$placeholder}";
            $bindings[$placeholder] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . $this->buildConditionsSql();

        return $this->execute($sql, $bindings)->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}" . $this->buildConditionsSql();
        return $this->execute($sql, $this->bindings)->rowCount();
    }

    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page = $page ?? (int) ($_GET['page'] ?? 1);
        $page = max(1, $page);

        $total = $this->count();
        $results = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return new Paginator($results, $total, $perPage, $page);
    }

    public function chunk(int $count, callable $callback): void
    {
        $page = 1;

        do {
            $results = $this->forPage($page, $count)->get();
            $countResults = count($results);

            if ($countResults === 0) {
                break;
            }

            if ($callback($results, $page) === false) {
                break;
            }

            $page++;
        } while ($countResults >= $count);
    }

    public function forPage(int $page, int $perPage): static
    {
        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function raw(string $sql, array $bindings = []): PDOStatement
    {
        return $this->execute($sql, $bindings);
    }

    protected function buildSelectSql(): string
    {
        $columns = implode(', ', $this->columns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        foreach ($this->joins as $join) {
            $sql .= " {$join}";
        }

        $sql .= $this->buildConditionsSql();

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if ($this->having !== null) {
            $sql .= " HAVING {$this->having}";
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function buildConditionsSql(): string
    {
        if (empty($this->conditions)) {
            return '';
        }

        $sql = ' WHERE ' . implode(' AND ', $this->conditions);
        return preg_replace('/WHERE OR/', 'WHERE', $sql);
    }

    protected function execute(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    public function reset(): static
    {
        $this->table = '';
        $this->conditions = [];
        $this->bindings = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->columns = ['*'];
        $this->joins = [];
        $this->groupBy = [];
        $this->having = null;

        return $this;
    }
}
