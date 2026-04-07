<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RemoveModel extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($name === null) {
            $this->error('Model name is required.');
            $this->info('Usage: php zen remove:model <name> [--force]');
            return;
        }

        $className = Str::studly($name);
        $path = __DIR__ . '/../../../app/Models/' . $className . '.php';

        if (!file_exists($path)) {
            $this->error("Model [{$className}] not found.");
            return;
        }

        if (!$force) {
            $this->warn("This will delete: {$path}");
            $confirm = $this->confirm("Are you sure you want to delete model [{$className}]?");
            if (!$confirm) {
                $this->info('Cancelled.');
                return;
            }
        }

        unlink($path);
        $this->info("Model [{$className}] deleted successfully.");
    }

    protected function confirm(string $message): bool
    {
        echo "{$message} [y/N]: ";
        $input = fgets(STDIN);
        if ($input === false) {
            return false;
        }
        return strtolower(trim($input)) === 'y';
    }
}
