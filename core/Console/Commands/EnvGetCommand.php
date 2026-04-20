<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class EnvGetCommand extends Command
{
    protected string $name = 'env:get';

    protected string $description = 'Get environment variable from .env';

    public function handle(Container $container, array $arguments): void
    {
        $key = $arguments[0] ?? null;

        if ($key === null) {
            $key = $this->ask('Enter environment variable name');
        }

        if (empty($key)) {
            $this->error('Key is required.');
            return;
        }

        $envFile = dirname(__DIR__, 3) . '/.env';

        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            return;
        }

        $value = env($key);

        if ($value === null) {
            $this->warn("Environment variable [{$key}] is not set.");
            return;
        }

        $this->line("{$key} = {$value}");
    }
}
