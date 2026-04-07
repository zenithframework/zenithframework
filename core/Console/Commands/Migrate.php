#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Console\Commands\DbSeed;

class Migrate extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $command = $arguments[0] ?? '';
        $rollback = in_array('--rollback', $arguments);
        $reset = in_array('--reset', $arguments);
        $refresh = in_array('--refresh', $arguments);
        $fresh = in_array('--fresh', $arguments) || $command === 'fresh';
        $seed = in_array('--seed', $arguments);

        if ($reset) {
            $this->info('Resetting migrations...');
            $runner = new \Zen\Database\MigrationRunner();
            $runner->reset();
            $this->info('Migrations reset complete.');
            return;
        }

        if ($rollback) {
            $this->info('Rolling back migrations...');
            $runner = new \Zen\Database\MigrationRunner();
            $runner->rollback();
            $this->info('Rollback complete.');
            return;
        }

        if ($refresh) {
            $this->info('Refreshing migrations (rollback + migrate)...');
            $runner = new \Zen\Database\MigrationRunner();
            $runner->rollback();
            $runner->run();
            $this->info('Migrations refreshed complete.');
            return;
        }

        if ($fresh) {
            $this->info('Dropping all tables and migrating fresh...');
            $runner = new \Zen\Database\MigrationRunner();
            $runner->fresh();
            $runner->run();

            if ($seed) {
                $this->info('Running seeders...');
                $seeder = new DbSeed($container);
                $seeder->handle($container, []);
            }

            $this->info('Fresh migration complete.');
            return;
        }

        $this->info('Running migrations...');
        $runner = new \Zen\Database\MigrationRunner();
        $runner->run();
        $this->info('Migrations complete.');
    }
}
