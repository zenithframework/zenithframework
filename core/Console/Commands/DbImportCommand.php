<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class DbImportCommand extends Command
{
    protected string $name = 'db:import';

    protected string $description = 'Import SQL file to database';

    public function handle(Container $container, array $arguments): void
    {
        $driver = env('DB_CONNECTION', 'sqlite');

        if ($driver !== 'sqlite') {
            $this->error('Import is currently only supported for SQLite databases.');
            $this->line('For MySQL/PostgreSQL, use native tools: mysql or psql');
            return;
        }

        $inputFile = $arguments[0] ?? null;

        if ($inputFile === null) {
            $inputFile = $this->ask('Enter the path to the SQL file');
        }

        if (empty($inputFile)) {
            $this->error('SQL file path is required.');
            return;
        }

        $basePath = dirname(__DIR__, 3);
        $fullPath = $inputFile;

        if (!file_exists($fullPath)) {
            $fullPath = $basePath . '/' . $inputFile;
        }

        if (!file_exists($fullPath)) {
            $this->error("SQL file not found: {$inputFile}");
            return;
        }

        $dbPath = $basePath . '/database/database.sqlite';

        if (file_exists($dbPath)) {
            $confirm = $this->confirm('This will overwrite the existing database. Continue?');

            if (!$confirm) {
                $this->warn('Import cancelled.');
                return;
            }

            unlink($dbPath);
        }

        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->info("Importing SQL file: {$inputFile}");

        try {
            $sqlite = new \SQLite3($dbPath);
            $sqlite->busyTimeout(5000);

            $sql = file_get_contents($fullPath);

            $statements = $this->parseSqlStatements($sql);

            $executed = 0;
            $errors = 0;

            foreach ($statements as $statement) {
                $statement = trim($statement);

                if (empty($statement) || str_starts_with($statement, '--')) {
                    continue;
                }

                try {
                    $sqlite->exec($statement);
                    $executed++;
                } catch (\SQLite3Exception $e) {
                    $errors++;

                    if ($errors <= 5) {
                        $this->warn("Warning: " . $e->getMessage());
                    }
                }
            }

            $sqlite->close();

            $this->line('');
            $this->info("Import completed successfully.");
            $this->line("  Statements executed: {$executed}");

            if ($errors > 0) {
                $this->warn("  Warnings: {$errors}");
            }
        } catch (\Throwable $e) {
            $this->error("Failed to import SQL file: " . $e->getMessage());

            if (file_exists($dbPath)) {
                unlink($dbPath);
            }
        }
    }

    protected function parseSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;

                if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                }

                if ($char === ';') {
                    $statements[] = $current;
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
