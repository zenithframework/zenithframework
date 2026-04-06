<?php

declare(strict_types=1);

namespace Zen\Database;

use PDO;

class MigrationRunner
{
    protected string $migrationPath;
    protected string $tableName = 'migrations';

    public function __construct()
    {
        $this->migrationPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    public function run(): void
    {
        $this->ensureMigrationsTable();

        $ran = $this->getRanMigrations();
        $files = $this->getMigrationFiles();
        $toRun = array_diff($files, $ran);

        foreach ($toRun as $file) {
            $this->runMigration($file);
        }
    }

    public function rollback(): void
    {
        $ran = $this->getRanMigrations();

        foreach (array_reverse($ran) as $migration) {
            $this->rollbackMigration($migration);
        }
    }

    public function reset(): void
    {
        $ran = $this->getRanMigrations();

        foreach (array_reverse($ran) as $migration) {
            $this->rollbackMigration($migration);
        }
    }

    protected function getRanMigrations(): array
    {
        $qb = new QueryBuilder();
        $qb->table($this->tableName);

        try {
            return $qb->orderBy('migration', 'DESC')->pluck('migration');
        } catch (\Throwable) {
            return [];
        }
    }

    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationPath)) {
            return [];
        }

        $files = glob($this->migrationPath . '/*.php');
        return array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $files ?? []);
    }

    protected function ensureMigrationsTable(): void
    {
        $qb = new QueryBuilder();
        $pdo = $qb->raw("SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->tableName}'");

        if ($pdo->fetch() === false) {
            $qb->raw("CREATE TABLE IF NOT EXISTS {$this->tableName} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
    }

    protected function runMigration(string $file): void
    {
        $migration = $this->getMigrationInstance($file);
        $migration->up();

        $batch = $this->getNextBatch();

        $qb = new QueryBuilder();
        $qb->table($this->tableName);
        $qb->insert([
            'migration' => $file,
            'batch' => $batch,
        ]);

        echo "Migrated: {$file}\n";
    }

    protected function rollbackMigration(string $file): void
    {
        $migration = $this->getMigrationInstance($file);
        $migration->down();

        $qb = new QueryBuilder();
        $qb->table($this->tableName);
        $qb->where('migration', $file);
        $qb->delete();

        echo "Rolled back: {$file}\n";
    }

    protected function getMigrationInstance(string $file): object
    {
        $filePath = $this->migrationPath . '/' . $file . '.php';
        
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }
        
        $migration = require $filePath;
        
        if (!$migration instanceof \Closure && !is_object($migration)) {
            throw new \RuntimeException("Invalid migration: {$file}");
        }
        
        if ($migration instanceof \Closure) {
            return new class($migration) {
                public function __construct(private \Closure $closure) {}
                public function up() { ($this->closure)(); }
                public function down() { ($this->closure)(); }
            };
        }
        
        return $migration;
    }

    protected function getNextBatch(): int
    {
        $qb = new QueryBuilder();
        $qb->table($this->tableName);

        $result = $qb->orderBy('batch', 'DESC')->first();
        return ($result['batch'] ?? 0) + 1;
    }

    public function getMigrationPath(): string
    {
        return $this->migrationPath;
    }

    public function setMigrationPath(string $path): void
    {
        $this->migrationPath = $path;
    }
}
