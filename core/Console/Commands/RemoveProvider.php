<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RemoveProvider extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($name === null) {
            $this->error('Provider name is required.');
            $this->info('Usage: php zen remove:provider <name> [--force]');
            return;
        }

        $className = Str::studly($name) . 'Provider';
        $path = __DIR__ . '/../../../app/Providers/' . $className . '.php';

        if (!file_exists($path)) {
            $this->error("Provider [{$className}] not found.");
            return;
        }

        if (!$force) {
            $this->warn("This will delete: {$path}");
            $confirm = $this->confirm("Are you sure you want to delete provider [{$className}]?");
            if (!$confirm) {
                $this->info('Cancelled.');
                return;
            }
        }

        unlink($path);
        $this->info("Provider [{$className}] deleted successfully.");
    }

    protected function confirm(string $message): bool
    {
        echo "{$message} [y/N]: ";
        $input = trim(fgets(STDIN));
        return strtolower($input) === 'y';
    }
}
