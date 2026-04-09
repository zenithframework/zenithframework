<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MakeComponent extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Component name is required.');
            $this->info('Usage: php zen make:component <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Components/' . $name . '.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Component [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Components;

class {$name}
{
    public function render(array \$data = []): string
    {
        return view('components.{$name}', \$data);
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Component [{$name}] created successfully.");
    }
}
