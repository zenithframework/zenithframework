<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class ConfigSetCommand extends Command
{
    protected string $name = 'config:set';

    protected string $description = 'Set configuration value';

    public function handle(Container $container, array $arguments): void
    {
        $key = $arguments[0] ?? null;
        $value = $arguments[1] ?? null;

        if ($key === null || $value === null) {
            $this->error('Usage: php zen config:set <key> <value>');
            $this->line('Example: php zen config:set app.name "My App"');
            return;
        }

        $this->warn('Note: Runtime configuration changes do not persist to config files.');
        $this->line('To permanently change configuration, edit the files in the config/ directory.');

        $configLoader = app()->make(\Zenith\Boot\ConfigLoader::class);
        $configLoader->set($key, $value);

        $this->info("Configuration [{$key}] has been set to [{$value}] for this request.");
    }
}
