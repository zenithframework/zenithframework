<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class AppKeyCommand extends Command
{
    protected string $name = 'app:key';

    protected string $description = 'Generate new application key';

    public function handle(Container $container, array $arguments): void
    {
        $force = in_array('--force', $arguments);
        $envFile = dirname(__DIR__, 3) . '/.env';

        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            return;
        }

        $envContent = file_get_contents($envFile);

        if ($envContent === false) {
            $this->error('Failed to read .env file.');
            return;
        }

        $hasKey = preg_match('/^APP_KEY=/m', $envContent);

        if ($hasKey && !$force) {
            $this->warn('Application key already exists.');
            $this->warn('Use --force to overwrite the existing key.');
            return;
        }

        $key = 'base64:' . base64_encode(random_bytes(32));

        if ($hasKey) {
            $envContent = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $envContent);
        } else {
            $envContent .= PHP_EOL . 'APP_KEY=' . $key;
        }

        if (file_put_contents($envFile, $envContent) === false) {
            $this->error('Failed to write .env file.');
            return;
        }

        $this->info('Application key generated successfully.');
        $this->line("  Key: {$key}");
        $this->line('');
        $this->warn('Remember to clear your cache and re-encrypt any encrypted data.');
    }
}
