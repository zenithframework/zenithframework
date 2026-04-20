<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeListener extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $event = $arguments[1] ?? null;

        if ($name === null) {
            $this->error('Listener name is required.');
            $this->info('Usage: php zen make:listener <name> [event]');
            return;
        }

        $className = Str::studly($name);
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../app/Listeners/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Listener [{$name}] already exists.");
            return;
        }

        $eventClass = $event ? Str::studly($event) : 'Event';
        $eventNamespace = $event ? "App\\Events\\{$eventClass}" : 'App\\Events\\Event';
        $useStatement = $event ? "use App\\Events\\{$eventClass};" : "";

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Listeners;

{$useStatement}

class {$className} extends Listener
{
    public function handle(\App\Events\Event \$event): void
    {
        // Handle the event
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Listener [{$className}] created successfully.");
    }
}
