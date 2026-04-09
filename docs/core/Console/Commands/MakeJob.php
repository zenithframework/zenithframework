<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeJob extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $sync = in_array('--sync', $arguments);
        $queue = in_array('--queue', $arguments);

        if ($name === null) {
            $this->error('Job name is required.');
            $this->info('Usage: php zen make:job <name> [--sync] [--queue]');
            return;
        }

        $className = Str::studly($name);
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../app/Jobs/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Job [{$name}] already exists.");
            return;
        }

        $type = $sync ? 'sync' : ($queue ? 'queue' : 'job');
        
        $content = match ($type) {
            'sync' => <<<PHP
<?php

declare(strict_types=1);

namespace App\Jobs;

class {$className} extends Job
{
    public function __construct(
        // public int \$id,
    ) {
    }

    public function handle(): void
    {
        // Process the job synchronously
    }
}
PHP,
            'queue' => <<<PHP
<?php

declare(strict_types=1);

namespace App\Jobs;

class {$className} extends Job
{
    public string \$queue = 'default';
    public int \$tries = 3;
    public int \$timeout = 120;

    public function __construct(
        // public int \$id,
    ) {
    }

    public function handle(): void
    {
        // Process the job
    }

    public function failed(\Throwable \$exception): void
    {
        // Handle job failure
    }
}
PHP,
            default => <<<PHP
<?php

declare(strict_types=1);

namespace App\Jobs;

class {$className} extends Job
{
    public function __construct(
        // public int \$id,
    ) {
    }

    public function handle(): void
    {
        // Process the job
    }
}
PHP,
        };

        file_put_contents($path, $content);
        $this->info("Job [{$className}] created successfully.");
    }
}
