<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RemoveSeeder extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($name === null) {
            $this->error('Seeder name is required.');
            $this->info('Usage: php zen remove:seeder <name> [--force]');
            return;
        }

        $className = Str::studly($name) . 'Seeder';
        $path = __DIR__ . '/../../../database/seeders/' . $className . '.php';

        if (!file_exists($path)) {
            $this->error("Seeder [{$className}] not found.");
            return;
        }

        if (!$force) {
            $this->warn("This will delete: {$path}");
            $confirm = $this->confirm("Are you sure you want to delete seeder [{$className}]?");
            if (!$confirm) {
                $this->info('Cancelled.');
                return;
            }
        }

        unlink($path);
        $this->info("Seeder [{$className}] deleted successfully.");
    }

    protected function confirm(string $message): bool
    {
        echo "{$message} [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }
}
