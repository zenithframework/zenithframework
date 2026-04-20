<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class AppDownCommand extends Command
{
    protected string $name = 'app:down';

    protected string $description = 'Put application in maintenance mode';

    public function handle(Container $container, array $arguments): void
    {
        $storagePath = dirname(__DIR__, 3) . '/storage/framework';
        $downFile = $storagePath . '/down';

        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        if (file_exists($downFile)) {
            $this->warn('Application is already in maintenance mode.');
            return;
        }

        $message = $this->ask('Maintenance message (optional)', 'Application is temporarily down for maintenance.');
        $retry = null;

        foreach ($arguments as $arg) {
            if (preg_match('/^--retry=(\d+)$/', $arg, $matches)) {
                $retry = (int) $matches[1];
            }
        }

        $data = [
            'time' => time(),
            'message' => $message,
        ];

        if ($retry !== null) {
            $data['retry'] = $retry;
        }

        if (file_put_contents($downFile, json_encode($data, JSON_PRETTY_PRINT)) === false) {
            $this->error('Failed to enable maintenance mode.');
            return;
        }

        $this->info('Application is now in maintenance mode.');
        $this->line("  Message: {$message}");

        if ($retry !== null) {
            $this->line("  Retry After: {$retry} seconds");
        }
    }
}
