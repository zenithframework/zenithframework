<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MakeService extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Service name is required.');
            $this->info('Usage: php zen make:service <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Services/' . $name . 'Service.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Service [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Services;

class {$name}Service
{
    public function handle(mixed ...\$args): mixed
    {
        return null;
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Service [{$name}Service] created successfully.");
    }
}
