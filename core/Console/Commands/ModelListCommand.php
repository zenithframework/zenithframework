<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\Model;

class ModelListCommand extends Command
{
    protected string $name = 'model:list';

    protected string $description = 'List all models with their table names';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $modelsDir = dirname(__DIR__, 3) . '/app/Models';

        if (!is_dir($modelsDir)) {
            $this->warn('No models directory found at app/Models');
            return;
        }

        $models = $this->discoverModels($modelsDir);

        if (empty($models)) {
            $this->warn('No models found.');
            return;
        }

        $modelList = [];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($modelClass);

                if ($reflection->isAbstract()) {
                    continue;
                }

                $instance = $reflection->newInstanceWithoutConstructor();
                $tableName = $instance->getTable();
                $primaryKey = $instance->getKeyName();

                $modelList[] = [
                    'model' => $modelClass,
                    'table' => $tableName,
                    'primary_key' => $primaryKey,
                ];
            } catch (\Throwable $e) {
                $this->warn("Skipping {$modelClass}: " . $e->getMessage());
            }
        }

        if ($json) {
            echo json_encode($modelList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Models');
        $this->line(str_repeat('-', 80));

        foreach ($modelList as $model) {
            $modelClass = str_pad($model['model'], 35);
            $table = str_pad($model['table'], 25);
            $key = $model['primary_key'];

            $this->line("  {$modelClass} {$table} {$key}");
        }

        $this->line(str_repeat('-', 80));
        $this->info('Total models: ' . count($modelList));
    }

    protected function discoverModels(string $directory, string $namespace = 'App\\Models'): array
    {
        $models = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $models[] = $className;
            }
        }

        return $models;
    }
}
