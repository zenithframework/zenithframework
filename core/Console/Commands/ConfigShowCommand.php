<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Boot\ConfigLoader;

class ConfigShowCommand extends Command
{
    protected string $name = 'config:show';

    protected string $description = 'Show configuration value';

    public function handle(Container $container, array $arguments): void
    {
        $key = $arguments[0] ?? null;

        if ($key === null) {
            $key = $this->ask('Enter configuration key (e.g., app.name)');
        }

        if (empty($key)) {
            $this->error('Configuration key is required.');
            return;
        }

        $configLoader = $container->make(ConfigLoader::class);
        $value = $configLoader->get($key);

        if ($value === null) {
            $this->warn("Configuration [{$key}] is not set.");
            return;
        }

        $this->line(str_repeat('-', 60));
        $this->info("Config: {$key}");
        $this->line(str_repeat('-', 60));

        if (is_array($value)) {
            $this->printArray($value);
        } else {
            $this->line((string) $value);
        }

        $this->line(str_repeat('-', 60));
    }

    protected function printArray(array $array, int $depth = 0): void
    {
        foreach ($array as $key => $value) {
            $indent = str_repeat('  ', $depth);

            if (is_array($value)) {
                $this->line("{$indent}{$key}:");
                $this->printArray($value, $depth + 1);
            } else {
                $this->line("{$indent}{$key}: {$value}");
            }
        }
    }
}
