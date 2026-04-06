#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class Migrate extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $rollback = in_array('--rollback', $arguments);
        $reset = in_array('--reset', $arguments);

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

        $this->info('Running migrations...');
        $runner = new \Zen\Database\MigrationRunner();
        $runner->run();
        $this->info('Migrations complete.');
    }
}
