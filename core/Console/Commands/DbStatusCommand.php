<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\QueryBuilder;

class DbStatusCommand extends Command
{
    protected string $name = 'db:status';

    protected string $description = 'Show database connection status';

    public function handle(Container $container, array $arguments): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');
        $config = config('database');
        $connection = $config['connections'][$driver] ?? [];

        $this->line(str_repeat('-', 60));
        $this->info('Database Connection Status');
        $this->line(str_repeat('-', 60));

        $this->line("  Driver:       {$driver}");

        if ($driver === 'sqlite') {
            $dbPath = $connection['database'] ?? (dirname(__DIR__, 3) . '/database/database.sqlite');
            $this->line("  Database:     {$dbPath}");
            $this->line("  Exists:       " . (file_exists($dbPath) ? 'Yes' : 'No'));
            $this->line("  Writable:     " . (is_writable(dirname($dbPath)) ? 'Yes' : 'No'));

            if (file_exists($dbPath)) {
                $size = filesize($dbPath);
                $this->line("  Size:         " . $this->formatBytes($size));
            }
        } elseif ($driver === 'mysql' || $driver === 'pgsql') {
            $this->line("  Host:         " . ($connection['host'] ?? '127.0.0.1'));
            $this->line("  Port:         " . ($connection['port'] ?? ($driver === 'mysql' ? '3306' : '5432')));
            $this->line("  Database:     " . ($connection['database'] ?? 'zen'));
            $this->line("  Username:     " . ($connection['username'] ?? 'root'));
        }

        $this->line("");
        $this->line("  Testing connection...");

        try {
            $qb = new QueryBuilder();
            $result = $qb->raw('SELECT 1');
            $this->line("  Status:       \033[32mConnected\033[0m");
        } catch (\Throwable $e) {
            $this->line("  Status:       \033[31mFailed\033[0m");
            $this->error("  Error:        " . $e->getMessage());
        }

        $this->line(str_repeat('-', 60));
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
