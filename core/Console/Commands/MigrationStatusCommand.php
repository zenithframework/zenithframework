<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\MigrationRunner;
use Zenith\Database\QueryBuilder;

class MigrationStatusCommand extends Command
{
    protected string $name = 'migration:status';

    protected string $description = 'Show migration status';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $runner = new MigrationRunner();
        $files = $this->getMigrationFiles($runner);

        if (empty($files)) {
            $this->warn('No migration files found.');
            return;
        }

        $ran = $this->getRanMigrations();
        $statusList = [];

        foreach ($files as $file) {
            $isRan = in_array($file, $ran);
            $statusList[] = [
                'migration' => $file,
                'status' => $isRan ? 'Migrated' : 'Pending',
                'batch' => $isRan ? $this->getBatchNumber($file) : '-',
            ];
        }

        if ($json) {
            echo json_encode($statusList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Migration Status');
        $this->line(str_repeat('-', 80));

        foreach ($statusList as $status) {
            $migration = str_pad($status['migration'], 50);
            $batch = str_pad((string) $status['batch'], 8);
            $statusLabel = $status['status'] === 'Migrated'
                ? "\033[32m{$status['status']}\033[0m"
                : "\033[33m{$status['status']}\033[0m";

            $this->line("  {$migration} {$batch} {$statusLabel}");
        }

        $migrated = count(array_filter($statusList, fn($s) => $s['status'] === 'Migrated'));
        $pending = count(array_filter($statusList, fn($s) => $s['status'] === 'Pending'));

        $this->line(str_repeat('-', 80));
        $this->info("Migrated: {$migrated} | Pending: {$pending}");
    }

    protected function getMigrationFiles(MigrationRunner $runner): array
    {
        $path = $runner->getMigrationPath();

        if (!is_dir($path)) {
            return [];
        }

        $files = glob($path . '/*.php');
        return array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $files ?: []);
    }

    protected function getRanMigrations(): array
    {
        $qb = new QueryBuilder();

        try {
            $pdo = $qb->raw("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");

            if ($pdo->fetch() === false) {
                return [];
            }

            $qb2 = new QueryBuilder();
            $qb2->table('migrations');
            return $qb2->pluck('migration');
        } catch (\Throwable) {
            return [];
        }
    }

    protected function getBatchNumber(string $migration): mixed
    {
        try {
            $qb = new QueryBuilder();
            $qb->table('migrations');
            $result = $qb->where('migration', $migration)->first();
            return $result['batch'] ?? '-';
        } catch (\Throwable) {
            return '-';
        }
    }
}
