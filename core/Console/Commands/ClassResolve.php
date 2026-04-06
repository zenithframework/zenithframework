<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class ClassResolve extends Command
{
    protected array $filesToProcess = [];

    public function handle(Container $container, array $arguments): void
    {
        $this->info('Scanning for missing imports...');

        $directories = ['app', 'core', 'boot'];
        $fix = in_array('--fix', $arguments) || in_array('-f', $arguments);

        foreach ($directories as $dir) {
            $path = __DIR__ . '/../../' . $dir;
            if (is_dir($path)) {
                $this->scanDirectory($path);
            }
        }

        $this->info('Found ' . count($this->filesToProcess) . ' files to process.');

        foreach ($this->filesToProcess as $file) {
            if ($fix) {
                $this->fixFile($file);
            } else {
                $this->analyzeFile($file);
            }
        }

        $fixMode = $fix ? 'Fixed' : 'Analyzed';
        $this->info("{$fixMode} " . count($this->filesToProcess) . " files.");
    }

    protected function scanDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->filesToProcess[] = $file->getPathname();
            }
        }
    }

    protected function analyzeFile(string $file): void
    {
        $content = file_get_contents($file);

        if (preg_match_all('/\\\\([A-Z][a-zA-Z0-9_\\\\]+)(?![A-Z])/', $content, $matches)) {
            foreach ($matches[1] as $class) {
                if (strpos($class, 'Zen\\') === 0 || strpos($class, 'App\\') === 0) {
                    $this->warn("Inline namespace used in {$file}: \\{$class}");
                }
            }
        }
    }

    protected function fixFile(string $file): void
    {
        $content = file_get_contents($file);

        if (preg_match_all('/\\\\([A-Z][a-zA-Z0-9_\\\\]+)(?![A-Z])/', $content, $matches)) {
            foreach ($matches[1] as $class) {
                if (strpos($class, 'Zen\\') === 0 || strpos($class, 'App\\') === 0) {
                    $content = str_replace('\\' . $class, $class, $content);
                    $this->info("Fixed inline namespace: \\{$class} in " . basename($file));
                }
            }
        }

        file_put_contents($file, $content);
    }
}
