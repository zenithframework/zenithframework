<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\QueryBuilder;

class DbDumpCommand extends Command
{
    protected string $name = 'db:dump';

    protected string $description = 'Export database to SQL file';

    public function handle(Container $container, array $arguments): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');

        if ($driver !== 'sqlite') {
            $this->error('Dump is currently only supported for SQLite databases.');
            $this->line('For MySQL/PostgreSQL, use native tools: mysqldump or pg_dump');
            return;
        }

        $outputFile = $arguments[0] ?? null;

        if ($outputFile === null) {
            $timestamp = date('Y-m-d_His');
            $outputFile = "database/dump_{$timestamp}.sql";
        }

        $dbPath = dirname(__DIR__, 3) . '/database/database.sqlite';

        if (!file_exists($dbPath)) {
            $this->error('SQLite database file not found.');
            return;
        }

        $fullPath = dirname(__DIR__, 3) . '/' . $outputFile;
        $outputDir = dirname($fullPath);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->info("Exporting database to: {$outputFile}");

        try {
            $sqlite = new \SQLite3($dbPath);
            $dump = $this->generateDump($sqlite);

            if (file_put_contents($fullPath, $dump) === false) {
                $this->error('Failed to write SQL file.');
                return;
            }

            $size = filesize($fullPath);
            $this->info("Database exported successfully. Size: " . $this->formatBytes($size));
        } catch (\Throwable $e) {
            $this->error("Failed to export database: " . $e->getMessage());
        }
    }

    protected function generateDump(\SQLite3 $sqlite): string
    {
        $dump = "-- Zenith Framework Database Dump\n";
        $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        $dump .= "BEGIN TRANSACTION;\n\n";

        // Export schema
        $tables = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        while ($row = $tables->fetchArray(\SQLITE3_NUM)) {
            $tableName = $row[0];

            // Get CREATE TABLE statement
            $createStmt = $sqlite->querySingle("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
            $dump .= "{$createStmt};\n\n";

            // Export data
            $data = $sqlite->query("SELECT * FROM '{$tableName}'");
            $columns = [];
            $numColumns = $data->numColumns();

            for ($i = 0; $i < $numColumns; $i++) {
                $columns[] = $data->columnName($i);
            }

            while ($row = $data->fetchArray(\SQLITE3_ASSOC)) {
                $values = array_map(function ($value) use ($sqlite) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . $sqlite->escapeString((string) $value) . "'";
                }, $row);

                $dump .= "INSERT INTO '{$tableName}' VALUES (" . implode(', ', $values) . ");\n";
            }

            $dump .= "\n";
        }

        // Export indexes
        $indexes = $sqlite->query("SELECT sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL");

        while ($row = $indexes->fetchArray(\SQLITE3_NUM)) {
            if ($row[0]) {
                $dump .= "{$row[0]};\n";
            }
        }

        $dump .= "\nCOMMIT;\n";

        return $dump;
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
