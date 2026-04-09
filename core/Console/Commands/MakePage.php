<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MakePage extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Page name is required.');
            $this->info('Usage: php zen make:page <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Pages/' . $name . '.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Page [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Pages;

class {$name}
{
    public function render(): string
    {
        return view('pages.{$name}');
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Page [{$name}] created successfully.");
    }
}
