<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeEvent extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Event name is required.');
            $this->info('Usage: php zen make:event <name>');
            return;
        }

        $className = Str::studly($name);
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../app/Events/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Event [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Events;

class {$className} extends Event
{
    public function __construct(
        // public int \$userId,
    ) {
    }

    public function broadcastOn(): array
    {
        // return ['channel'];
        return [];
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Event [{$className}] created successfully.");
    }
}
