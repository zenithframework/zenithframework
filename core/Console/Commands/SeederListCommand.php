<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class SeederListCommand extends Command
{
    protected string $name = 'seeder:list';

    protected string $description = 'List all seeders';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $seedersDir = dirname(__DIR__, 3) . '/database/seeders';

        if (!is_dir($seedersDir)) {
            $this->warn('No seeders directory found at database/seeders');
            return;
        }

        $seeders = $this->discoverSeeders($seedersDir);

        if (empty($seeders)) {
            $this->warn('No seeders found.');
            return;
        }

        $seederList = [];

        foreach ($seeders as $seederClass) {
            if (!class_exists($seederClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($seederClass);

                if ($reflection->isAbstract()) {
                    continue;
                }

                $description = $this->extractDescription($reflection);

                $seederList[] = [
                    'seeder' => $seederClass,
                    'description' => $description,
                ];
            } catch (\Throwable $e) {
                $this->warn("Skipping {$seederClass}: " . $e->getMessage());
            }
        }

        if ($json) {
            echo json_encode($seederList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Seeders');
        $this->line(str_repeat('-', 80));

        foreach ($seederList as $seeder) {
            $class = str_pad($seeder['seeder'], 45);
            $desc = $seeder['description'] ?: '(no description)';
            $this->line("  {$class} {$desc}");
        }

        $this->line(str_repeat('-', 80));
        $this->info('Total seeders: ' . count($seederList));
    }

    protected function discoverSeeders(string $directory, string $namespace = 'Database\\Seeders'): array
    {
        $seeders = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $seeders[] = $className;
            }
        }

        return $seeders;
    }

    protected function extractDescription(\ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();

        if ($docComment === false) {
            return '';
        }

        preg_match('/\*\s*(.+?)(?:\n|\*\/)/', $docComment, $matches);

        if (isset($matches[1])) {
            return trim(trim($matches[1], '* '));
        }

        return '';
    }
}
