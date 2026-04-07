<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

class MakeProject extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Project name is required.');
            $this->info('Usage: php zen new <projectname>');
            $this->info('Example: php zen new my-app');
            return;
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name)) {
            $this->error('Invalid project name. Use letters, numbers, hyphens, and underscores only.');
            return;
        }

        $targetDir = getcwd() . '/' . $name;

        if (is_dir($targetDir)) {
            $this->error("Directory [{$name}] already exists.");
            return;
        }

        $this->info("Creating project: {$name}...");

        $this->createProjectStructure($name, $targetDir);

        $this->info("Project [{$name}] created successfully!");
        $this->info("");
        $this->info("Next steps:");
        $this->info("  cd {$name}");
        $this->info("  php zen serve");
    }

    protected function createProjectStructure(string $name, string $targetDir): void
    {
        mkdir($targetDir, 0755, true);

        $directories = [
            'app/Http/Controllers',
            'app/Http/Middleware',
            'app/Http/Requests',
            'app/Models',
            'app/Pages',
            'app/Services',
            'app/Providers',
            'app/UI/Components',
            'boot',
            'config',
            'core/AI',
            'core/Action',
            'core/Auth',
            'core/Cache',
            'core/Console/Commands',
            'core/Database',
            'core/Diagnostics',
            'core/Event',
            'core/Http',
            'core/Log',
            'core/Middleware',
            'core/Queue',
            'core/Routing',
            'core/Security',
            'core/Session',
            'core/Storage',
            'core/Support',
            'core/UI',
            'core/Validation',
            'database/migrations',
            'database/seeders',
            'database/factories',
            'public',
            'routes',
            'storage/framework/views',
            'storage/framework/cache',
            'storage/logs',
            'storage/sessions',
            'storage/cache',
            'views/layouts',
            'views/pages',
            'views/components',
        ];

        foreach ($directories as $dir) {
            mkdir($targetDir . '/' . $dir, 0755, true);
        }

        $this->copyFrameworkFiles($targetDir);
        $this->createEnvFile($targetDir, $name);
        $this->createPublicIndex($targetDir);
        $this->createZenCli($targetDir);
        $this->createRoutes($targetDir);
        $this->createWelcomePage($targetDir);
        $this->createConfigFiles($targetDir);
        $this->createDatabase($targetDir);
    }

    protected function copyFrameworkFiles(string $targetDir): void
    {
        // From core/Console/Commands - go up 3 levels to get to zen root
        $baseDir = dirname(__DIR__, 3);
        
        // Debug - list files in baseDir
        if (!is_dir($baseDir)) {
            $this->error("Base directory not found: {$baseDir}");
            return;
        }
        
        $filesToCopy = [
            'boot/Autoloader.php',
            'boot/ConfigLoader.php',
            'boot/Engine.php',
            'boot/Ignition.php',
            'boot/RouteLoader.php',
            'core/Container.php',
            'core/Support/helpers.php',
        ];
        
        foreach ($filesToCopy as $file) {
            $src = $baseDir . '/' . $file;
            if (file_exists($src)) {
                copy($src, $targetDir . '/' . $file);
            }
        }
        
        $coreDirs = ['AI', 'Action', 'Auth', 'Cache', 'Database', 'Diagnostics', 'Event', 'Http', 'Log', 'Middleware', 'Queue', 'Routing', 'Security', 'Session', 'Storage', 'Support', 'UI', 'Validation'];
        
        foreach ($coreDirs as $dir) {
            $srcDir = $baseDir . '/core/' . $dir;
            $destDir = $targetDir . '/core/' . $dir;
            if (is_dir($srcDir)) {
                $this->copyDirectory($srcDir, $destDir);
            }
        }
        
        $cmdSrc = $baseDir . '/core/Console/Commands';
        $cmdDest = $targetDir . '/core/Console/Commands';
        if (is_dir($cmdSrc)) {
            $commands = ['Command.php', 'CacheClear.php', 'ClassResolve.php', 'DbSeed.php', 'Lint.php', 'MakeComponent.php', 'MakeController.php', 'MakeFactory.php', 'MakeJob.php', 'MakeLayout.php', 'MakeListener.php', 'MakeMigration.php', 'MakeModel.php', 'MakePage.php', 'MakeProvider.php', 'MakeRequest.php', 'MakeSeeder.php', 'MakeService.php', 'Migrate.php', 'RemoveComponent.php', 'RemoveController.php', 'RemoveFactory.php', 'RemoveJob.php', 'RemoveListener.php', 'RemoveLayout.php', 'RemoveMiddleware.php', 'RemoveModel.php', 'RemovePage.php', 'RemoveProvider.php', 'RemoveRequest.php', 'RemoveSeeder.php', 'RemoveService.php', 'RenameController.php', 'RenameModel.php', 'RenameService.php', 'RouteCache.php', 'RouteList.php', 'RouteTest.php', 'Serve.php', 'Test.php'];
            foreach ($commands as $cmd) {
                $src = $cmdSrc . '/' . $cmd;
                if (file_exists($src)) {
                    copy($src, $cmdDest . '/' . $cmd);
                }
            }
        }
    }

    protected function copyDirectory(string $src, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $srcFile = $src . '/' . $file;
            $destFile = $dest . '/' . $file;
            
            if (is_dir($srcFile)) {
                $this->copyDirectory($srcFile, $destFile);
            } else {
                copy($srcFile, $destFile);
            }
        }
    }

    protected function createEnvFile(string $targetDir, string $name): void
    {
        $env = <<<ENV
APP_NAME={$name}
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file

AI_API_KEY=
ENV;

        file_put_contents($targetDir . '/.env', $env);
    }

    protected function createRoutes(string $targetDir): void
    {
        $web = <<<PHP
<?php

declare(strict_types=1);

use App\Pages\Welcome;

\$router->get('/', [Welcome::class, 'render']);

return \$router;
PHP;

        $api = <<<PHP
<?php

declare(strict_types=1);

use App\Http\Controllers\ApiController;

\$router->get('/status', [ApiController::class, 'status']);

return \$router;
PHP;

        $auth = <<<PHP
<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;

\$router->get('/login', [AuthController::class, 'showLogin']);
\$router->post('/login', [AuthController::class, 'login']);
\$router->post('/logout', [AuthController::class, 'logout']);
\$router->get('/register', [AuthController::class, 'showRegister']);
\$router->post('/register', [AuthController::class, 'register']);

return \$router;
PHP;

        file_put_contents($targetDir . '/routes/Web.php', $web);
        file_put_contents($targetDir . '/routes/Api.php', $api);
        file_put_contents($targetDir . '/routes/Auth.php', $auth);
    }

    protected function createWelcomePage(string $targetDir): void
    {
        $page = <<<PHP
<?php

declare(strict_types=1);

namespace App\Pages;

class Welcome
{
    public function render(): string
    {
        return view('pages.welcome');
    }
}
PHP;

        $view = <<<HTML
@extends('layouts.main')

@section('title', 'Welcome')

@section('content')
<div class="text-center py-16">
    <h1 class="text-4xl font-bold mb-4">Welcome to Your Zen App</h1>
    <p class="text-xl text-gray-600 mb-8">Built with Zen Framework v2.0</p>
    <div class="flex gap-4 justify-center">
        <a href="/docs" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Documentation
        </a>
        <a href="/api/status" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
            API Status
        </a>
    </div>
</div>
@endsection
HTML;

        $layout = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Zen App')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-xl font-bold text-gray-800">Zen App</a>
            <div class="flex gap-4">
                <a href="/docs" class="text-gray-600 hover:text-gray-800">Docs</a>
                <a href="/api/status" class="text-gray-600 hover:text-gray-800">API</a>
            </div>
        </div>
    </nav>
    
    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
    
    <footer class="bg-gray-800 text-white text-center py-4 mt-16">
        <p>&copy; {{ date('Y') }} Zen App. Built with Zen Framework.</p>
    </footer>
</body>
</html>
HTML;

        file_put_contents($targetDir . '/app/Pages/Welcome.php', $page);
        file_put_contents($targetDir . '/views/pages/welcome.zen.php', $view);
        file_put_contents($targetDir . '/views/layouts/main.zen.php', $layout);
    }

    protected function createPublicIndex(string $targetDir): void
    {
        $index = <<<'PHP'
<?php

define('ZEN_START', microtime(true));

require_once __DIR__ . '/../boot/Ignition.php';

$container = Zen\Boot\Ignition::fire();

$router = $container->make(\Zen\Routing\Router::class);

foreach (['Web', 'Api', 'Auth', 'Ai'] as $routeFile) {
    $file = __DIR__ . '/../routes/' . $routeFile . '.php';
    if (file_exists($file)) {
        require $file;
    }
}

$request = \Zen\Http\Request::capture();
$response = $router->match($request);

if ($response === null) {
    http_response_code(404);
    echo "404 Not Found";
    exit(1);
}

$response->send();
PHP;

        file_put_contents($targetDir . '/public/index.php', $index);
    }

    protected function createZenCli(string $targetDir): void
    {
        $zen = <<<'ZEN'
#!/usr/bin/env php
<?php

declare(strict_types=1);

define('ZEN_START', microtime(true));

require_once __DIR__ . '/boot/Ignition.php';

use Zen\Boot\Ignition;
use Zen\Console\Commands;

$container = Ignition::fire();

$commands = [
    'make:page' => Commands\MakePage::class,
    'make:component' => Commands\MakeComponent::class,
    'make:middleware' => Commands\MakeMiddleware::class,
    'make:controller' => Commands\MakeController::class,
    'make:model' => Commands\MakeModel::class,
    'make:service' => Commands\MakeService::class,
    'make:migration' => Commands\MakeMigration::class,
    'make:provider' => Commands\MakeProvider::class,
    'make:seeder' => Commands\MakeSeeder::class,
    'make:factory' => Commands\MakeFactory::class,
    'make:layout' => Commands\MakeLayout::class,
    'make:request' => Commands\MakeRequest::class,
    'make:job' => Commands\MakeJob::class,
    'migrate' => Commands\Migrate::class,
    'migrate:rollback' => Commands\Migrate::class,
    'db:seed' => Commands\DbSeed::class,
    'cache:clear' => Commands\CacheClear::class,
    'serve' => Commands\Serve::class,
    'lint' => Commands\Lint::class,
    'test' => Commands\Test::class,
];

if (PHP_SAPI !== 'cli') {
    exit(1);
}

array_shift($argv);

if (empty($argv)) {
    echo "Zen Framework CLI\n";
    echo "Usage: php zen <command> [arguments]\n";
    exit(0);
}

$command = $argv[0];
$args = array_slice($argv, 1);

if (!isset($commands[$command])) {
    echo "Command '{$command}' not found.\n";
    exit(1);
}

$class = $commands[$command];
$cmd = new $class();
$cmd->handle($container, $args);
ZEN;

        file_put_contents($targetDir . '/zen', $zen);
        chmod($targetDir . '/zen', 0755);
    }

    protected function createConfigFiles(string $targetDir): void
    {
        $app = <<<PHP
<?php

return [
    'name' => 'Zen App',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost:8080',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY', 'base64:' . base64_encode(random_bytes(32))),
    'cipher' => 'AES-256-CBC',
    'providers' => [
        \App\Providers\AppProvider::class,
    ],
];
PHP;

        $database = <<<PHP
<?php

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/database/database.sqlite',
            'prefix' => '',
        ],
    ],
];
PHP;

        file_put_contents($targetDir . '/config/app.php', $app);
        file_put_contents($targetDir . '/config/database.php', $database);
    }

    protected function createDatabase(string $targetDir): void
    {
        $sqlite = $targetDir . '/database/database.sqlite';
        touch($sqlite);
    }
}
