<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class FactoryListCommand extends Command
{
    protected string $name = 'factory:list';

    protected string $description = 'List all model factories';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $factoriesDir = dirname(__DIR__, 3) . '/database/factories';

        if (!is_dir($factoriesDir)) {
            $this->warn('No factories directory found at database/factories');
            return;
        }

        $factories = $this->discoverFactories($factoriesDir);

        if (empty($factories)) {
            $this->warn('No factories found.');
            return;
        }

        $factoryList = [];

        foreach ($factories as $factoryClass) {
            if (!class_exists($factoryClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($factoryClass);

                if ($reflection->isAbstract()) {
                    continue;
                }

                $model = $this->extractModel($reflection);

                $factoryList[] = [
                    'factory' => $factoryClass,
                    'model' => $model ?: 'Unknown',
                ];
            } catch (\Throwable $e) {
                $this->warn("Skipping {$factoryClass}: " . $e->getMessage());
            }
        }

        if ($json) {
            echo json_encode($factoryList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Model Factories');
        $this->line(str_repeat('-', 80));

        foreach ($factoryList as $factory) {
            $factoryClass = str_pad($factory['factory'], 45);
            $model = $factory['model'];
            $this->line("  {$factoryClass} => {$model}");
        }

        $this->line(str_repeat('-', 80));
        $this->info('Total factories: ' . count($factoryList));
    }

    protected function discoverFactories(string $directory, string $namespace = 'Database\\Factories'): array
    {
        $factories = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $factories[] = $className;
            }
        }

        return $factories;
    }

    protected function extractModel(\ReflectionClass $reflection): ?string
    {
        $docComment = $reflection->getDocComment();

        if ($docComment !== false) {
            preg_match('/@model\s+(\S+)/', $docComment, $matches);

            if (isset($matches[1])) {
                return $matches[1];
            }
        }

        $className = $reflection->getShortName();

        if (str_ends_with($className, 'Factory')) {
            $modelName = substr($className, 0, -7);

            if (class_exists('App\\Models\\' . $modelName)) {
                return 'App\\Models\\' . $modelName;
            }
        }

        return null;
    }
}
