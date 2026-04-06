<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class MakeLayout extends Command
{
    protected array $layouts = [
        'app' => 'Authenticated layout with sidebar and header',
        'guest' => 'Public layout for landing pages',
        'blank' => 'Minimal empty layout',
        'auth' => 'Login/Register layout',
        'dashboard' => 'Admin dashboard layout with sidebar',
        'custom' => 'Custom user-defined layout',
    ];

    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? 'default';
        $type = 'app';
        
        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--type=')) {
                $type = substr($arg, 7);
            }
        }

        if (!isset($this->layouts[$type]) && $type !== 'custom') {
            $this->error("Invalid layout type: {$type}");
            $this->info("Available types: " . implode(', ', array_keys($this->layouts)));
            return;
        }

        $className = Str::studly($name);
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../views/layouts/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Layout [{$name}] already exists.");
            return;
        }

        $content = $this->getLayoutContent($type, $className);

        file_put_contents($path, $content);
        $this->info("Layout [{$className}] created successfully (type: {$type}).");
    }

    protected function getLayoutContent(string $type, string $name): string
    {
        return match ($type) {
            'app' => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Zen Framework'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <a href="/" class="text-xl font-bold text-blue-600">Zen Framework</a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="/" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Dashboard</a>
            <a href="/users" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Users</a>
            <a href="/settings" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Settings</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold"><?php echo \$title ?? ''; ?></h1>
                <div class="flex items-center space-x-4">
                    <?php if (auth()->check()): ?>
                        <span class="text-gray-600"><?php echo auth()->user()->name ?? 'User'; ?></span>
                        <form method="POST" action="/auth/logout">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-red-600 hover:text-red-700">Logout</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6">
            <?php echo \$content ?? ''; ?>
        </main>
    </div>
</body>
</html>
HTML,
            'guest' => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Zen Framework'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-2xl font-bold text-blue-600">Zen Framework</a>
            <div class="flex items-center space-x-4">
                <a href="/auth/login" class="text-gray-600 hover:text-gray-900">Login</a>
                <a href="/auth/register" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Content -->
    <main class="flex-1 flex items-center justify-center">
        <div class="text-center">
            <?php echo \$content ?? ''; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500">
            &copy; <?php echo date('Y'); ?> Zen Framework. All rights reserved.
        </div>
    </footer>
</body>
</html>
HTML,
            'blank' => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Zen Framework'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <?php echo \$content ?? ''; ?>
</body>
</html>
HTML,
            'auth' => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Zen Framework - Auth'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="text-2xl font-bold text-blue-600">Zen Framework</a>
            <p class="text-gray-600 mt-2"><?php echo \$subtitle ?? 'Sign in to your account'; ?></p>
        </div>
        <?php echo \$content ?? ''; ?>
    </div>
</body>
</html>
HTML,
            'dashboard' => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Dashboard'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-700">
            <a href="/" class="text-xl font-bold text-blue-400">Admin Panel</a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="/admin" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg">
                <i class="fas fa-home w-6"></i> Dashboard
            </a>
            <a href="/admin/users" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg">
                <i class="fas fa-users w-6"></i> Users
            </a>
            <a href="/admin/settings" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 rounded-lg">
                <i class="fas fa-cog w-6"></i> Settings
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Top Bar -->
        <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold"><?php echo \$title ?? ''; ?></h1>
                <div class="flex items-center space-x-4">
                    <button class="text-gray-400 hover:text-white"><i class="fas fa-bell"></i></button>
                    <span class="text-gray-300">Admin</span>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 p-6">
            <?php echo \$content ?? ''; ?>
        </main>
    </div>
</body>
</html>
HTML,
            default => <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \$title ?? 'Zen Framework'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php echo \$content ?? ''; ?>
    </main>
</body>
</html>
HTML,
        };
    }
}
