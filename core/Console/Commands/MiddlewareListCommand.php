<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MiddlewareListCommand extends Command
{
    protected string $name = 'middleware:list';

    protected string $description = 'List all middleware with descriptions';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $middlewareDir = dirname(__DIR__, 3) . '/app/Http/Middleware';

        if (!is_dir($middlewareDir)) {
            $this->warn('No middleware directory found at app/Http/Middleware');
            return;
        }

        $middlewares = $this->discoverMiddleware($middlewareDir);

        if (empty($middlewares)) {
            $this->warn('No middleware found.');
            return;
        }

        $middlewareList = [];

        foreach ($middlewares as $middlewareClass) {
            if (!class_exists($middlewareClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($middlewareClass);
                $description = $this->extractDescription($reflection);

                $middlewareList[] = [
                    'middleware' => $middlewareClass,
                    'description' => $description,
                ];
            } catch (\Throwable $e) {
                $this->warn("Skipping {$middlewareClass}: " . $e->getMessage());
            }
        }

        if ($json) {
            echo json_encode($middlewareList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Middleware');
        $this->line(str_repeat('-', 80));

        foreach ($middlewareList as $middleware) {
            $class = str_pad($middleware['middleware'], 45);
            $desc = $middleware['description'] ?: '(no description)';
            $this->line("  {$class} {$desc}");
        }

        $this->line(str_repeat('-', 80));
        $this->info('Total middleware: ' . count($middlewareList));
    }

    protected function discoverMiddleware(string $directory, string $namespace = 'App\\Http\\Middleware'): array
    {
        $middlewares = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $middlewares[] = $className;
            }
        }

        return $middlewares;
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
