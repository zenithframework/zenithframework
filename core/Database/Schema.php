<?php

declare(strict_types=1);

namespace Zenith\Database;

use PDO;

class Schema
{
    protected static ?PDO $connection = null;
    protected static string $driver = 'sqlite';

    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = self::buildCreateSql($blueprint);
        self::execute($sql);
    }

    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);
        
        $sql = self::buildAlterSql($blueprint);
        
        if (!empty($sql)) {
            self::execute($sql);
        }
    }

    public static function drop(string $table): void
    {
        $sql = match (self::$driver) {
            'mysql' => "DROP TABLE IF EXISTS {$table}",
            'pgsql' => "DROP TABLE IF EXISTS {$table}",
            default => "DROP TABLE IF EXISTS {$table}",
        };
        self::execute($sql);
    }

    public static function dropIfExists(string $table): void
    {
        self::drop($table);
    }

    public static function hasTable(string $table): bool
    {
        $pdo = self::getConnection();
        
        return match (self::$driver) {
            'mysql' => self::hasTableMysql($pdo, $table),
            'pgsql' => self::hasTablePgsql($pdo, $table),
            default => self::hasTableSqlite($pdo, $table),
        };
    }

    protected static function hasTableSqlite(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
        return $stmt->fetch() !== false;
    }

    protected static function hasTableMysql(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}'");
        return (int) $stmt->fetchColumn() > 0;
    }

    protected static function hasTablePgsql(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '{$table}'");
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (!self::hasTable($table)) {
            return false;
        }
        
        $pdo = self::getConnection();
        
        return match (self::$driver) {
            'mysql' => self::hasColumnMysql($pdo, $table, $column),
            'pgsql' => self::hasColumnPgsql($pdo, $table, $column),
            default => self::hasColumnSqlite($pdo, $table, $column),
        };
    }

    protected static function hasColumnSqlite(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            if ($col['name'] === $column) {
                return true;
            }
        }
        return false;
    }

    protected static function hasColumnMysql(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$table}' AND column_name = '{$column}'");
        return (int) $stmt->fetchColumn() > 0;
    }

    protected static function hasColumnPgsql(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' AND column_name = '{$column}'");
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function getColumnListing(string $table): array
    {
        if (!self::hasTable($table)) {
            return [];
        }
        
        $pdo = self::getConnection();
        
        return match (self::$driver) {
            'mysql' => self::getColumnListingMysql($pdo, $table),
            'pgsql' => self::getColumnListingPgsql($pdo, $table),
            default => self::getColumnListingSqlite($pdo, $table),
        };
    }

    protected static function getColumnListingSqlite(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_column($columns, 'name');
    }

    protected static function getColumnListingMysql(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$table}' ORDER BY ordinal_position");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    protected static function getColumnListingPgsql(PDO $pdo, string $table): array
    {
        $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{$table}' ORDER BY ordinal_position");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function enableForeignKeyConstraints(): void
    {
        $sql = match (self::$driver) {
            'mysql' => 'SET FOREIGN_KEY_CHECKS = 1',
            'pgsql' => 'SET CONSTRAINTS ALL IMMEDIATE',
            default => 'PRAGMA foreign_keys = ON',
        };
        self::execute($sql);
    }

    public static function disableForeignKeyConstraints(): void
    {
        $sql = match (self::$driver) {
            'mysql' => 'SET FOREIGN_KEY_CHECKS = 0',
            'pgsql' => 'SET CONSTRAINTS ALL DEFERRED',
            default => 'PRAGMA foreign_keys = OFF',
        };
        self::execute($sql);
    }

    protected static function buildCreateSql(Blueprint $blueprint): string
    {
        $table = $blueprint->getTable();
        $columns = $blueprint->getColumns();
        $indexes = $blueprint->getIndexes();
        
        $columnDefs = [];
        
        foreach ($columns as $column) {
            $columnDefs[] = self::buildColumnDefinition($column);
        }
        
        $sql = match (self::$driver) {
            'mysql' => self::buildCreateSqlMysql($table, $columnDefs, $indexes),
            'pgsql' => self::buildCreateSqlPgsql($table, $columnDefs, $indexes),
            default => self::buildCreateSqlSqlite($table, $columnDefs, $indexes),
        };
        
        return $sql;
    }

    protected static function buildCreateSqlSqlite(string $table, array $columnDefs, array $indexes): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (" . implode(', ', $columnDefs);
        
        $primaryIndexes = array_filter($indexes, fn($idx) => $idx['type'] === 'primary');
        foreach ($primaryIndexes as $index) {
            $cols = implode(', ', $index['columns']);
            $sql .= ", PRIMARY KEY ({$cols})";
        }
        
        $sql .= ")";
        
        return $sql;
    }

    protected static function buildCreateSqlMysql(string $table, array $columnDefs, array $indexes): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (\n  " . implode(",\n  ", $columnDefs);
        
        $primaryIndexes = array_filter($indexes, fn($idx) => $idx['type'] === 'primary');
        foreach ($primaryIndexes as $index) {
            $cols = implode(', ', $index['columns']);
            $sql .= ",\n  PRIMARY KEY ({$cols})";
        }
        
        $uniqueIndexes = array_filter($indexes, fn($idx) => $idx['type'] === 'unique');
        foreach ($uniqueIndexes as $index) {
            $cols = implode(', ', $index['columns']);
            $sql .= ",\n  UNIQUE {$index['name']} ({$cols})";
        }
        
        $regularIndexes = array_filter($indexes, fn($idx) => $idx['type'] === 'index');
        foreach ($regularIndexes as $index) {
            $cols = implode(', ', $index['columns']);
            $sql .= ",\n  INDEX {$index['name']} ({$cols})";
        }
        
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        return $sql;
    }

    protected static function buildCreateSqlPgsql(string $table, array $columnDefs, array $indexes): string
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (" . implode(', ', $columnDefs);
        
        $primaryIndexes = array_filter($indexes, fn($idx) => $idx['type'] === 'primary');
        foreach ($primaryIndexes as $index) {
            $cols = implode(', ', $index['columns']);
            $sql .= ", PRIMARY KEY ({$cols})";
        }
        
        $sql .= ")";
        
        return $sql;
    }

    protected static function buildAlterSql(Blueprint $blueprint): string
    {
        return '';
    }

    protected static function buildColumnDefinition(array $column): string
    {
        $name = $column['name'];
        $type = self::getSqlType($column);
        
        return match (self::$driver) {
            'mysql' => self::buildColumnDefinitionMysql($name, $type, $column),
            'pgsql' => self::buildColumnDefinitionPgsql($name, $type, $column),
            default => self::buildColumnDefinitionSqlite($name, $type, $column),
        };
    }

    protected static function buildColumnDefinitionSqlite(string $name, string $type, array $column): string
    {
        $parts = ["{$name} {$type}"];
        
        if (!empty($column['primary']) && $column['auto_increment'] ?? false) {
            $parts[] = 'PRIMARY KEY AUTOINCREMENT';
        }
        
        if ($column['nullable'] ?? false) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }
        
        if ($column['unique'] ?? false) {
            $parts[] = 'UNIQUE';
        }
        
        if (array_key_exists('default', $column)) {
            $parts[] = self::buildDefaultValue($column['default']);
        }
        
        return implode(' ', $parts);
    }

    protected static function buildColumnDefinitionMysql(string $name, string $type, array $column): string
    {
        $parts = ["`{$name}` {$type}"];
        
        if ($column['auto_increment'] ?? false) {
            $parts[] = 'AUTO_INCREMENT';
        }
        
        if ($column['nullable'] ?? false) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }
        
        if ($column['unique'] ?? false) {
            $parts[] = 'UNIQUE';
        }
        
        if (array_key_exists('default', $column)) {
            $parts[] = self::buildDefaultValue($column['default']);
        }
        
        if (!empty($column['comment'] ?? '')) {
            $parts[] = "COMMENT '{$column['comment']}'";
        }
        
        return implode(' ', $parts);
    }

    protected static function buildColumnDefinitionPgsql(string $name, string $type, array $column): string
    {
        $parts = ["\"{$name}\" {$type}"];
        
        if ($column['nullable'] ?? false) {
            $parts[] = 'NULL';
        } else {
            $parts[] = 'NOT NULL';
        }
        
        if ($column['unique'] ?? false) {
            $parts[] = 'UNIQUE';
        }
        
        if (array_key_exists('default', $column)) {
            $parts[] = self::buildDefaultValue($column['default']);
        }
        
        return implode(' ', $parts);
    }

    protected static function buildDefaultValue(mixed $default): string
    {
        if ($default === null) {
            return 'DEFAULT NULL';
        }
        
        if (is_bool($default)) {
            return 'DEFAULT ' . ($default ? 'TRUE' : 'FALSE');
        }
        
        if (is_numeric($default)) {
            return "DEFAULT {$default}";
        }
        
        $upper = strtoupper($default);
        if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'])) {
            return "DEFAULT {$upper}";
        }
        
        return "DEFAULT '{$default}'";
    }

    protected static function getSqlType(array $column): string
    {
        $type = $column['type'] ?? 'varchar';
        
        return match (self::$driver) {
            'mysql' => self::getSqlTypeMysql($type, $column),
            'pgsql' => self::getSqlTypePgsql($type, $column),
            default => self::getSqlTypeSqlite($type, $column),
        };
    }

    protected static function getSqlTypeSqlite(string $type, array $column): string
    {
        return match ($type) {
            'varchar', 'string' => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'text', 'longtext', 'mediumtext', 'tinytext' => strtoupper($type),
            'integer', 'tinyint', 'smallint' => 'INTEGER',
            'bigint', 'unsigned_bigint' => 'BIGINT',
            'unsigned_integer' => 'INTEGER',
            'float', 'double' => strtoupper($type),
            'decimal' => 'DECIMAL(' . ($column['precision'] ?? 8) . ',' . ($column['scale'] ?? 2) . ')',
            'boolean', 'bool' => 'TINYINT(1)',
            'date', 'datetime', 'timestamp', 'time', 'year' => strtoupper($type),
            'binary', 'blob' => 'BLOB',
            'json', 'jsonb' => 'TEXT',
            'uuid' => 'VARCHAR(36)',
            'ip_address' => 'VARCHAR(45)',
            'mac_address' => 'VARCHAR(17)',
            'enum' => 'TEXT',
            'set' => 'TEXT',
            default => strtoupper($type),
        };
    }

    protected static function getSqlTypeMysql(string $type, array $column): string
    {
        return match ($type) {
            'varchar', 'string' => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'text' => 'TEXT',
            'longtext' => 'LONGTEXT',
            'mediumtext' => 'MEDIUMTEXT',
            'tinytext' => 'TINYTEXT',
            'integer' => 'INT',
            'tinyint' => 'TINYINT',
            'smallint' => 'SMALLINT',
            'bigint' => 'BIGINT',
            'unsigned_bigint' => 'BIGINT UNSIGNED',
            'unsigned_integer' => 'INT UNSIGNED',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'decimal' => 'DECIMAL(' . ($column['precision'] ?? 8) . ',' . ($column['scale'] ?? 2) . ')',
            'boolean', 'bool' => 'TINYINT(1)',
            'date' => 'DATE',
            'datetime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'time' => 'TIME',
            'year' => 'YEAR',
            'binary', 'blob' => 'BLOB',
            'json' => 'JSON',
            'jsonb' => 'JSONB',
            'uuid' => 'CHAR(36)',
            'ip_address' => 'VARCHAR(45)',
            'mac_address' => 'VARCHAR(17)',
            'enum' => 'ENUM(' . implode(', ', array_map(fn($v) => "'{$v}'", $column['allowed'] ?? [])) . ')',
            'set' => 'SET(' . implode(', ', array_map(fn($v) => "'{$v}'", $column['allowed'] ?? [])) . ')',
            default => strtoupper($type),
        };
    }

    protected static function getSqlTypePgsql(string $type, array $column): string
    {
        return match ($type) {
            'varchar', 'string' => 'VARCHAR(' . ($column['length'] ?? 255) . ')',
            'text', 'longtext', 'mediumtext', 'tinytext' => 'TEXT',
            'integer' => 'INTEGER',
            'tinyint' => 'SMALLINT',
            'smallint' => 'SMALLINT',
            'bigint' => 'BIGINT',
            'unsigned_bigint' => 'BIGINT',
            'unsigned_integer' => 'INTEGER',
            'float' => 'REAL',
            'double' => 'DOUBLE PRECISION',
            'decimal' => 'DECIMAL(' . ($column['precision'] ?? 8) . ',' . ($column['scale'] ?? 2) . ')',
            'boolean', 'bool' => 'BOOLEAN',
            'date' => 'DATE',
            'datetime', 'timestamp' => 'TIMESTAMP',
            'time' => 'TIME',
            'year' => 'SMALLINT',
            'binary', 'blob' => 'BYTEA',
            'json' => 'JSON',
            'jsonb' => 'JSONB',
            'uuid' => 'UUID',
            'ip_address' => 'INET',
            'mac_address' => 'MACADDR',
            'enum' => self::buildPgsqlEnumType($column['name'], $column['allowed'] ?? []),
            'set' => 'TEXT',
            default => strtoupper($type),
        };
    }

    protected static function buildPgsqlEnumType(string $name, array $allowed): string
    {
        return 'TEXT';
    }

    protected static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $databasePath = dirname(__DIR__, 4) . '/database/database.sqlite';
        $username = '';
        $password = '';

        $config = config('database');
        if ($config !== null) {
            self::$driver = $config['default'] ?? 'sqlite';
            $connection = $config['connections'][self::$driver] ?? [];
            
            if (isset($connection['database'])) {
                $databasePath = $connection['database'];
            }
            if (isset($connection['username'])) {
                $username = $connection['username'];
            }
            if (isset($connection['password'])) {
                $password = $connection['password'];
            }
        }

        $dsn = match (self::$driver) {
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $connection['host'] ?? '127.0.0.1',
                $connection['port'] ?? '3306',
                $connection['database'] ?? 'zen',
                $connection['charset'] ?? 'utf8mb4'
            ),
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $connection['host'] ?? '127.0.0.1',
                $connection['port'] ?? '5432',
                $connection['database'] ?? 'zen'
            ),
            default => 'sqlite:' . $databasePath,
        };

        self::$connection = new PDO($dsn, $username, $password);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return self::$connection;
    }

    protected static function execute(string $sql): void
    {
        $pdo = self::getConnection();
        $pdo->exec($sql);
    }

    public static function getDriver(): string
    {
        return self::$driver;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
