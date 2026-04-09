<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class ControllerListCommand extends Command
{
    protected string $name = 'controller:list';

    protected string $description = 'List all controllers with methods';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $controllersDir = dirname(__DIR__, 3) . '/app/Http/Controllers';

        if (!is_dir($controllersDir)) {
            $this->warn('No controllers directory found at app/Http/Controllers');
            return;
        }

        $controllers = $this->discoverControllers($controllersDir);

        if (empty($controllers)) {
            $this->warn('No controllers found.');
            return;
        }

        $controllerList = [];

        foreach ($controllers as $controllerClass) {
            if (!class_exists($controllerClass)) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($controllerClass);

                if ($reflection->isAbstract()) {
                    continue;
                }

                $methods = [];
                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($method->class === $controllerClass) {
                        $methods[] = $method->getName();
                    }
                }

                $controllerList[] = [
                    'controller' => $controllerClass,
                    'methods' => $methods,
                ];
            } catch (\Throwable $e) {
                $this->warn("Skipping {$controllerClass}: " . $e->getMessage());
            }
        }

        if ($json) {
            echo json_encode($controllerList, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Controllers');
        $this->line(str_repeat('-', 80));

        foreach ($controllerList as $controller) {
            $this->line('');
            $this->info("  {$controller['controller']}");

            foreach ($controller['methods'] as $method) {
                $this->line("    - {$method}()");
            }
        }

        $this->line('');
        $this->line(str_repeat('-', 80));
        $this->info('Total controllers: ' . count($controllerList));
    }

    protected function discoverControllers(string $directory, string $namespace = 'App\\Http\\Controllers'): array
    {
        $controllers = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $className = $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $controllers[] = $className;
            }
        }

        return $controllers;
    }
}
