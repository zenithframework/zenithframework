<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class MakeController extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Controller name is required.');
            $this->info('Usage: php zen make:controller <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Http/Controllers/' . $name . 'Controller.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Controller [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class {$name}Controller
{
    public function index(Request \$request): Response
    {
        return response('{$name}Controller@index');
    }

    public function show(Request \$request, mixed ...\$params): Response
    {
        return response('{$name}Controller@show');
    }

    public function store(Request \$request): Response
    {
        return response('{$name}Controller@store', 201);
    }

    public function update(Request \$request, mixed ...\$params): Response
    {
        return response('{$name}Controller@update');
    }

    public function destroy(Request \$request, mixed ...\$params): Response
    {
        return response('', 204);
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Controller [{$name}Controller] created successfully.");
    }
}
