<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class EnvSetCommand extends Command
{
    protected string $name = 'env:set';

    protected string $description = 'Set environment variable in .env';

    public function handle(Container $container, array $arguments): void
    {
        $key = $arguments[0] ?? null;
        $value = $arguments[1] ?? null;

        if ($key === null) {
            $key = $this->ask('Enter environment variable name');
        }

        if ($value === null) {
            $value = $this->ask('Enter value');
        }

        if (empty($key) || empty($value)) {
            $this->error('Both key and value are required.');
            return;
        }

        $envFile = dirname(__DIR__, 3) . '/.env';

        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            return;
        }

        $content = file_get_contents($envFile);
        $escapedValue = $this->escapeValue($value);

        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$escapedValue}", $content);
            $this->info("Updated existing variable: {$key}");
        } else {
            $content .= "\n{$key}={$escapedValue}\n";
            $this->info("Added new variable: {$key}");
        }

        if (file_put_contents($envFile, $content) === false) {
            $this->error('Failed to write to .env file.');
            return;
        }

        $this->info("Successfully set {$key}={$value}");
    }

    protected function escapeValue(string $value): string
    {
        if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '=') || str_contains($value, '"')) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }

        return $value;
    }
}
