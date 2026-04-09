<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class Lint extends Command
{
    protected bool $checkImports = true;

    public function handle(Container $container, array $arguments): void
    {
        $this->checkImports = !in_array('--no-imports', $arguments);

        $dirs = [
            __DIR__ . '/../../../app',
            __DIR__ . '/../../../core',
            __DIR__ . '/../../../boot',
        ];

        $errors = 0;
        $files = 0;
        $importErrors = 0;

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $files++;
                $output = [];
                $result = 0;
                exec("php -l " . escapeshellarg($file->getPathname()) . " 2>&1", $output, $result);

                if ($result !== 0) {
                    $errors++;
                    $this->error("{$file->getPathname()}: " . implode("\n", $output));
                }

                if ($this->checkImports) {
                    $importResult = $this->checkImportsInFile($file->getPathname());
                    if ($importResult) {
                        $importErrors++;
                    }
                }
            }
        }

        $this->line('');
        $this->info("Linted {$files} files.");

        if ($errors > 0) {
            $this->error("Found {$errors} file(s) with syntax errors.");
            exit(1);
        }

        if ($this->checkImports && $importErrors > 0) {
            $this->error("Found {$importErrors} file(s) with import issues.");
            exit(1);
        }

        $this->info('No errors found.');
    }

    protected function checkImportsInFile(string $file): bool
    {
        $content = file_get_contents($file);
        $hasError = false;

        if (preg_match_all('/\\\\([A-Z][a-zA-Z0-9_\\\\]+)(?![a-z])/', $content, $matches)) {
            foreach ($matches[1] as $class) {
                if ((strpos($class, 'Zen\\') === 0 || strpos($class, 'App\\') === 0) 
                    && strpos($class, '\\\\') !== false) {
                    $this->warn("Inline namespace: \\{$class} in " . basename($file));
                    $hasError = true;
                }
            }
        }

        return $hasError;
    }
}
