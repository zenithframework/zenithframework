<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Database\Model;

class ModelShowCommand extends Command
{
    protected string $name = 'model:show';

    protected string $description = 'Show model details (columns, relationships)';

    public function handle(Container $container, array $arguments): void
    {
        $modelName = $arguments[0] ?? null;

        if ($modelName === null) {
            $modelName = $this->ask('Enter the model name (e.g., User)');
        }

        if (empty($modelName)) {
            $this->error('Model name is required.');
            return;
        }

        $modelClass = $this->resolveModelClass($modelName);

        if ($modelClass === null || !class_exists($modelClass)) {
            $this->error("Model [{$modelName}] not found.");
            return;
        }

        try {
            $reflection = new \ReflectionClass($modelClass);

            if ($reflection->isAbstract()) {
                $this->error("Model [{$modelName}] is abstract and cannot be instantiated.");
                return;
            }

            $instance = $reflection->newInstanceWithoutConstructor();
            $tableName = $instance->getTable();
            $primaryKey = $instance->getKeyName();
            $fillable = $this->getPropertyValue($instance, 'fillable');
            $guarded = $this->getPropertyValue($instance, 'guarded');
            $hidden = $this->getPropertyValue($instance, 'hidden');
            $casts = $this->getPropertyValue($instance, 'casts');
            $timestamps = $this->getPropertyValue($instance, 'timestamps');
            $relationships = $this->findRelationships($reflection);

            $this->line(str_repeat('-', 60));
            $this->info("Model: {$modelClass}");
            $this->line(str_repeat('-', 60));
            $this->line("  Table:          {$tableName}");
            $this->line("  Primary Key:    {$primaryKey}");
            $this->line("  Timestamps:     " . ($timestamps ? 'Yes' : 'No'));

            $this->line('');
            $this->info('Fillable:');
            if (empty($fillable)) {
                $this->line("  (none - all fields fillable by default)");
            } else {
                foreach ($fillable as $field) {
                    $this->line("  - {$field}");
                }
            }

            $this->line('');
            $this->info('Guarded:');
            if (empty($guarded)) {
                $this->line("  (none)");
            } else {
                foreach ($guarded as $field) {
                    $this->line("  - {$field}");
                }
            }

            $this->line('');
            $this->info('Hidden:');
            if (empty($hidden)) {
                $this->line("  (none)");
            } else {
                foreach ($hidden as $field) {
                    $this->line("  - {$field}");
                }
            }

            $this->line('');
            $this->info('Casts:');
            if (empty($casts)) {
                $this->line("  (none)");
            } else {
                foreach ($casts as $field => $type) {
                    $this->line("  - {$field}: {$type}");
                }
            }

            $this->line('');
            $this->info('Relationships:');
            if (empty($relationships)) {
                $this->line("  (none found)");
            } else {
                foreach ($relationships as $rel) {
                    $this->line("  - {$rel['method']} => {$rel['type']} ({$rel['related']})");
                }
            }

            $this->line(str_repeat('-', 60));
        } catch (\Throwable $e) {
            $this->error("Failed to load model details: " . $e->getMessage());
        }
    }

    protected function resolveModelClass(string $name): ?string
    {
        $modelsDir = dirname(__DIR__, 3) . '/app/Models';

        if (class_exists($name)) {
            return $name;
        }

        if (class_exists('App\\Models\\' . $name)) {
            return 'App\\Models\\' . $name;
        }

        $file = $modelsDir . '/' . $name . '.php';

        if (file_exists($file)) {
            return 'App\\Models\\' . $name;
        }

        return null;
    }

    protected function getPropertyValue(Model $instance, string $property): mixed
    {
        try {
            $reflection = new \ReflectionClass($instance);
            $prop = $reflection->getProperty($property);
            return $prop->getValue($instance);
        } catch (\Throwable) {
            return [];
        }
    }

    protected function findRelationships(\ReflectionClass $reflection): array
    {
        $relationshipTypes = [
            'belongsTo',
            'hasOne',
            'hasMany',
            'belongsToMany',
            'morphTo',
            'morphOne',
            'morphMany',
            'hasManyThrough',
        ];

        $relationships = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) {
                continue;
            }

            $filename = $method->getFileName();
            if ($filename === false) {
                continue;
            }

            $content = file_get_contents($filename);
            if ($content === false) {
                continue;
            }

            $lines = file($filename);
            if ($lines === false) {
                continue;
            }

            $startLine = $method->getStartLine() - 1;
            $endLine = $method->getEndLine();
            $methodBody = implode('', array_slice($lines, $startLine, $endLine - $startLine));

            foreach ($relationshipTypes as $type) {
                if (strpos($methodBody, '->' . $type . '(') !== false) {
                    preg_match('/->' . $type . '\(\s*[\'"]([^\'"]+)[\'"]/', $methodBody, $matches);
                    $related = $matches[1] ?? 'Unknown';

                    $relationships[] = [
                        'method' => $method->getName(),
                        'type' => $type,
                        'related' => $related,
                    ];

                    break;
                }
            }
        }

        return $relationships;
    }
}
