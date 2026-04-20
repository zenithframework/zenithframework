<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class QueueTableCommand extends Command
{
    protected string $name = 'queue:table';

    protected string $description = 'Create queue failed jobs table';

    public function handle(Container $container, array $arguments): void
    {
        $this->info('Creating queue failed jobs table...');

        $migrationDir = dirname(__DIR__, 3) . '/database/migrations';

        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $migrationFile = $migrationDir . '/' . $timestamp . '_create_failed_jobs_table.php';

        if (file_exists($migrationFile)) {
            $this->warn('Migration already exists. Skipping.');
            return;
        }

        $migrationContent = <<<'PHP'
<?php

declare(strict_types=1);

return new class {
    public function up(): void
    {
        $qb = new \Zenith\Database\QueryBuilder();
        $qb->raw("CREATE TABLE IF NOT EXISTS failed_jobs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            connection TEXT NOT NULL,
            queue TEXT NOT NULL,
            payload TEXT NOT NULL,
            exception TEXT NOT NULL,
            failed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function down(): void
    {
        $qb = new \Zenith\Database\QueryBuilder();
        $qb->raw("DROP TABLE IF EXISTS failed_jobs");
    }
};
PHP;

        if (file_put_contents($migrationFile, $migrationContent) === false) {
            $this->error('Failed to create migration file.');
            return;
        }

        $this->info('Migration created successfully: ' . basename($migrationFile));
        $this->line('');
        $this->warn('Run "php zen migrate" to apply the migration.');
    }
}
