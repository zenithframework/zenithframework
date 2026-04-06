<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class DbSeed extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $class = $arguments[0] ?? null;
        $force = in_array('--force', $arguments);

        if ($class !== null && $class !== '--force') {
            $this->runSingleSeeder($class);
            return;
        }

        $seederPath = __DIR__ . '/../../../database/seeders/';
        
        if (!is_dir($seederPath)) {
            $this->warn('No seeders directory found.');
            return;
        }

        $files = glob($seederPath . '*.php');
        
        if (empty($files)) {
            $this->warn('No seeders found.');
            return;
        }

        $this->info('Running seeders...');
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            
            require_once $file;
            
            if (!class_exists($filename)) {
                continue;
            }
            
            $seeder = new $filename();
            $seeder->run();
            
            $this->info("Seeded: {$filename}");
        }

        $this->info('Database seeded successfully.');
    }

    protected function runSingleSeeder(string $class): void
    {
        $className = str_ends_with($class, 'Seeder') ? $class : $class . 'Seeder';
        $path = __DIR__ . '/../../../database/seeders/' . $className . '.php';

        if (!file_exists($path)) {
            $this->error("Seeder [{$className}] not found.");
            return;
        }

        require_once $path;

        if (!class_exists($className)) {
            $this->error("Seeder class [{$className}] not found.");
            return;
        }

        $seeder = new $className();
        $seeder->run();

        $this->info("Seeder [{$className}] run successfully.");
    }
}
