<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\QueryBuilder;

class DbTablesCommand extends Command
{
    protected string $name = 'db:tables';

    protected string $description = 'List all database tables';

    public function handle(Container $container, array $arguments): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');
        $qb = new QueryBuilder();

        $this->info('Fetching database tables...');
        $this->line('');

        try {
            if ($driver === 'sqlite') {
                $tables = $qb->raw("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
            } elseif ($driver === 'mysql') {
                $database = config('database.connections.mysql.database') ?? 'zen';
                $tables = $qb->raw("SHOW TABLES FROM `{$database}`")->fetchAll(\PDO::FETCH_COLUMN);
            } elseif ($driver === 'pgsql') {
                $tables = $qb->raw("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename")->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $this->error("Unsupported database driver: {$driver}");
                return;
            }

            if (empty($tables)) {
                $this->warn('No tables found in the database.');
                return;
            }

            $this->line(str_repeat('-', 50));
            $this->info('Database Tables');
            $this->line(str_repeat('-', 50));

            foreach ($tables as $table) {
                $count = $qb->table($table)->count();
                $this->line(sprintf("  %-40s %d rows", $table, $count));
            }

            $this->line(str_repeat('-', 50));
            $this->info("Total: " . count($tables) . " tables");
        } catch (\Throwable $e) {
            $this->error("Failed to list tables: " . $e->getMessage());
        }
    }
}
