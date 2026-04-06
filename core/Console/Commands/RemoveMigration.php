<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RemoveMigration extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($name === null) {
            $this->error('Migration name is required.');
            $this->info('Usage: php zen remove:migration <name> [--force]');
            return;
        }

        $path = __DIR__ . '/../../../database/migrations/';
        $files = glob($path . '*' . Str::snake($name) . '.php');

        if (empty($files)) {
            $this->error("Migration [{$name}] not found.");
            return;
        }

        $file = $files[0];
        $filename = basename($file);

        if (!$force) {
            $this->warn("This will delete: {$filename}");
            $confirm = $this->confirm("Are you sure you want to delete migration [{$filename}]?");
            if (!$confirm) {
                $this->info('Cancelled.');
                return;
            }
        }

        unlink($file);
        $this->info("Migration [{$filename}] deleted successfully.");
    }

    protected function confirm(string $message): bool
    {
        echo "{$message} [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }
}
