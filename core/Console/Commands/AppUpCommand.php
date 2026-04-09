<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class AppUpCommand extends Command
{
    protected string $name = 'app:up';

    protected string $description = 'Bring application out of maintenance mode';

    public function handle(Container $container, array $arguments): void
    {
        $storagePath = dirname(__DIR__, 3) . '/storage/framework';
        $downFile = $storagePath . '/down';

        if (!file_exists($downFile)) {
            $this->warn('Application is not in maintenance mode.');
            return;
        }

        if (unlink($downFile)) {
            $this->info('Application is now live.');
        } else {
            $this->error('Failed to disable maintenance mode.');
        }
    }
}
