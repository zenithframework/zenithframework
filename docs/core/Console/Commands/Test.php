<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class Test extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $testDir = __DIR__ . '/../../../tests';

        if (!is_dir($testDir)) {
            $this->warn('No tests directory found.');
            return;
        }

        $phpunit = __DIR__ . '/../../../vendor/bin/phpunit';

        if (file_exists($phpunit)) {
            passthru($phpunit . ' ' . implode(' ', array_map('escapeshellarg', $arguments)), $exitCode);
            exit($exitCode);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($testDir)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Test.php')) {
                $files[] = $file->getPathname();
            }
        }

        if (empty($files)) {
            $this->warn('No test files found.');
            return;
        }

        $passed = 0;
        $failed = 0;

        require $testDir . '/TestCase.php';

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $className = 'Tests\\' . $filename;

            require $file;

            if (!class_exists($className)) {
                $this->warn("Class {$className} not found in {$file}");
                continue;
            }

            $test = new $className();

            foreach (get_class_methods($test) as $method) {
                if (strncmp($method, 'test', 4) !== 0) {
                    continue;
                }

                try {
                    $test->{$method}();
                    $this->info("✓ {$className}::{$method}");
                    $passed++;
                } catch (\Throwable $e) {
                    $this->error("✗ {$className}::{$method} - {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->line('');
        $this->info("Passed: {$passed}");

        if ($failed > 0) {
            $this->error("Failed: {$failed}");
            exit(1);
        }
    }
}
