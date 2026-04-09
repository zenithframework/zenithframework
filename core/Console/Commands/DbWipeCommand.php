<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\QueryBuilder;

class DbWipeCommand extends Command
{
    protected string $name = 'db:wipe';

    protected string $description = 'Drop all tables and reset database';

    public function handle(Container $container, array $arguments): void
    {
        $force = in_array('--force', $arguments);

        if (!$force) {
            $confirm = $this->confirm('This will drop all tables and reset the database. Continue?');

            if (!$confirm) {
                $this->warn('Database wipe cancelled.');
                return;
            }
        }

        $driver = env('DB_CONNECTION', 'sqlite');

        if ($driver === 'sqlite') {
            $this->wipeSqlite();
        } elseif ($driver === 'mysql') {
            $this->wipeMysql($container);
        } elseif ($driver === 'pgsql') {
            $this->wipePgsql($container);
        } else {
            $this->error("Unsupported database driver: {$driver}");
            return;
        }

        $this->info('Database wiped successfully.');
    }

    protected function wipeSqlite(): void
    {
        $dbPath = dirname(__DIR__, 3) . '/database/database.sqlite';

        if (file_exists($dbPath)) {
            unlink($dbPath);
            $this->info('SQLite database file deleted.');
        } else {
            $this->warn('SQLite database file not found. Nothing to wipe.');
        }

        $this->info('Create a fresh database by running: php zen migrate');
    }

    protected function wipeMysql(Container $container): void
    {
        $config = config('database');
        $connection = $config['connections']['mysql'] ?? [];

        try {
            $pdo = new \PDO(
                sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $connection['host'] ?? '127.0.0.1', $connection['port'] ?? '3306'),
                $connection['username'] ?? '',
                $connection['password'] ?? ''
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $database = $connection['database'] ?? 'zen';

            $stmt = $pdo->query("SHOW TABLES FROM `{$database}`");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($tables)) {
                $this->warn('No tables found in database.');
                return;
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$database}`.`{$table}`");
                $this->line("  Dropped: {$table}");
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\PDOException $e) {
            $this->error("Failed to wipe MySQL database: " . $e->getMessage());
        }
    }

    protected function wipePgsql(Container $container): void
    {
        $config = config('database');
        $connection = $config['connections']['pgsql'] ?? [];

        try {
            $pdo = new \PDO(
                sprintf('pgsql:host=%s;port=%s', $connection['host'] ?? '127.0.0.1', $connection['port'] ?? '5432'),
                $connection['username'] ?? '',
                $connection['password'] ?? ''
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $database = $connection['database'] ?? 'zen';

            $pdo->exec("DROP SCHEMA public CASCADE; CREATE SCHEMA public;");
            $this->info("All tables in database '{$database}' dropped.");
        } catch (\PDOException $e) {
            $this->error("Failed to wipe PostgreSQL database: " . $e->getMessage());
        }
    }
}
