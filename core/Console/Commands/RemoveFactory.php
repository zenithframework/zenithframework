<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RemoveFactory extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($name === null) {
            $this->error('Factory name is required.');
            $this->info('Usage: php zen remove:factory <name> [--force]');
            return;
        }

        $className = Str::studly($name) . 'Factory';
        $path = __DIR__ . '/../../../database/factories/' . $className . '.php';

        if (!file_exists($path)) {
            $this->error("Factory [{$className}] not found.");
            return;
        }

        if (!$force) {
            $this->warn("This will delete: {$path}");
            $confirm = $this->confirm("Are you sure you want to delete factory [{$className}]?");
            if (!$confirm) {
                $this->info('Cancelled.');
                return;
            }
        }

        unlink($path);
        $this->info("Factory [{$className}] deleted successfully.");
    }

    protected function confirm(string $message): bool
    {
        echo "{$message} [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }
}
