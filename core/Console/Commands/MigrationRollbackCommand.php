<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\MigrationRunner;
use Zenith\Database\QueryBuilder;

class MigrationRollbackCommand extends Command
{
    protected string $name = 'migration:rollback';

    protected string $description = 'Rollback specific number of migrations';

    public function handle(Container $container, array $arguments): void
    {
        $step = 1;

        foreach ($arguments as $arg) {
            if (preg_match('/^--step=(\d+)$/', $arg, $matches)) {
                $step = (int) $matches[1];
                break;
            }
        }

        $all = in_array('--all', $arguments);

        if ($all) {
            $this->info('Rolling back all migrations...');
            $runner = new MigrationRunner();
            $runner->reset();
            $this->info('All migrations rolled back.');
            return;
        }

        $this->info("Rolling back {$step} migration(s)...");

        $ran = $this->getRanMigrations();

        if (empty($ran)) {
            $this->warn('No migrations to rollback.');
            return;
        }

        $toRollback = array_slice(array_reverse($ran), 0, $step);

        foreach ($toRollback as $migration) {
            $this->line("  Rolling back: {$migration}");
            $this->rollbackMigration($migration);
        }

        $this->info("Rolled back " . count($toRollback) . " migration(s).");
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
            return $qb2->orderBy('batch', 'DESC')->orderBy('migration', 'DESC')->pluck('migration');
        } catch (\Throwable) {
            return [];
        }
    }

    protected function rollbackMigration(string $file): void
    {
        $runner = new MigrationRunner();
        $migration = $this->getMigrationInstance($runner, $file);

        try {
            $migration->down();

            $qb = new QueryBuilder();
            $qb->table('migrations');
            $qb->where('migration', $file);
            $qb->delete();

            $this->info("  Rolled back: {$file}");
        } catch (\Throwable $e) {
            $this->error("  Failed to rollback {$file}: " . $e->getMessage());
        }
    }

    protected function getMigrationInstance(MigrationRunner $runner, string $file): object
    {
        $filePath = $runner->getMigrationPath() . '/' . $file . '.php';

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
}
