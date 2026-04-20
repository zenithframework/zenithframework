<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MakeMiddleware extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Middleware name is required.');
            $this->info('Usage: php zen make:middleware <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Http/Middleware/' . $name . '.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Middleware [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Zenith\Http\Request;
use Zenith\Http\Response;

class {$name}
{
    public function handle(Request \$request, Closure \$next): ?Response
    {
        return \$next(\$request);
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Middleware [{$name}] created successfully.");
    }
}
