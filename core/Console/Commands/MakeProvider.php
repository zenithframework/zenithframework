<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class MakeProvider extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Provider name is required.');
            $this->info('Usage: php zen make:provider <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Providers/' . $name . 'Provider.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Provider [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Providers;

use Zen\Boot\ServiceProvider;

class {$name}Provider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Provider [{$name}Provider] created successfully.");
    }
}
