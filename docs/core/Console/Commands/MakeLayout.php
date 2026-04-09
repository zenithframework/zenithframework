<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeLayout extends Command
{
    protected array $layouts = [
        'app' => 'Authenticated layout with sidebar and header (RECOMMENDED)',
        'main' => 'Full SEO layout with navbar, sidebar, footer',
        'guest' => 'Public layout for landing pages',
        'blank' => 'Minimal empty layout',
        'auth' => 'Login/Register layout',
        'dashboard' => 'Admin dashboard layout with dark theme',
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
        $filename = $className . '.zen.php';
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
        $appNamePhp = "<?php \$appName = config('app.name', 'Zen'); ?>";
        
        return match ($type) {
            'app' => $appNamePhp . "\n" . <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= $appName ?>')</title>
    <meta name="description" content="@yield('description', '<?= $appName ?> - Clean, Fast, Modern PHP')">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <a href="/" class="text-xl font-bold text-blue-600"><?= $appName ?></a>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="/" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Dashboard</a>
            <a href="/users" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Users</a>
            <a href="/settings" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">Settings</a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold"><?= \$appName ?></h1>
                <div class="flex items-center space-x-4">
                    @auth
                        <span class="text-gray-600">{{ auth()->user()->name }}</span>
                        <form method="POST" action="/auth/logout">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-700">Logout</button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
HTML,
            'main' => $appName . <<<HTML

<!DOCTYPE html>
<html lang="{{ \$lang ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= \$appName ?>')</title>
    
    <!-- SEO -->
    <meta name="description" content="@yield('description', '<?= \$appName ?> - Clean, Fast, Modern PHP')">
    <meta name="keywords" content="@yield('keywords', '')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og:title', '')">
    <meta property="og:description" content="@yield('og:description', '')">
    <meta property="og:image" content="@yield('og:image', '')">
    <meta property="og:type" content="@yield('og:type', 'website')">
    
    <link rel="canonical" href="@yield('canonical', '')">
    <link rel="icon" href="@yield('favicon', 'favicon.ico')">
    
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('head')
</head>
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900">
    <a href="#main-content" class="sr-only focus:not-sr-only">Skip to content</a>
    
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-gray-900"><?= \$appName ?></a>
            <ul class="hidden md:flex gap-6">
                @yield('nav')
            </ul>
        </nav>
    </header>
    
    <div class="flex flex-1">
        @hasSection('sidebar')
            <aside class="w-64 bg-white border-r hidden lg:block">
                @yield('sidebar')
            </aside>
        @endif
        
        <main id="main-content" class="flex-1 container mx-auto px-4 py-8">
            @yield('content')
        </main>
    </div>
    
    <footer class="bg-gray-900 text-gray-300 mt-auto">
        <div class="container mx-auto px-4 py-8 text-center">
            <p>&copy; {{ date('Y') }} <?= \$appName ?>. All rights reserved.</p>
        </div>
    </footer>
    
    @yield('scripts')
</body>
</html>
HTML,
            'guest' => $appName . <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= \$appName ?>')</title>
    <meta name="description" content="@yield('description', '')">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('head')
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-2xl font-bold text-blue-600"><?= \$appName ?></a>
            <div class="flex gap-4">
                <a href="/auth/login" class="text-gray-600 hover:text-gray-900">Login</a>
                <a href="/auth/register" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Get Started</a>
            </div>
        </div>
    </nav>

    <main class="flex-1 flex items-center justify-center">
        @yield('content')
    </main>

    <footer class="bg-white border-t py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500">
            &copy; {{ date('Y') }} <?= \$appName ?>. All rights reserved.
        </div>
    </footer>
    
    @yield('scripts')
</body>
</html>
HTML,
            'blank' => $appName . <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= \$appName ?>')</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('head')
</head>
<body>
    @yield('content')
    @yield('scripts')
</body>
</html>
HTML,
            'auth' => $appName . <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= \$appName ?> - Auth')</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('head')
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="text-2xl font-bold text-blue-600"><?= \$appName ?></a>
            <p class="text-gray-600 mt-2">@yield('subtitle', 'Sign in to your account')</p>
        </div>
        @yield('content')
    </div>
    @yield('scripts')
</body>
</html>
HTML,
            'dashboard' => $appName . <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard - <?= \$appName ?>')</title>
    <meta name="description" content="@yield('description', 'Admin Dashboard')">
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('head')
</head>
<body class="bg-gray-900 text-white min-h-screen flex">
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

    <div class="flex-1 flex flex-col">
        <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">@yield('title', 'Dashboard')</h1>
                <div class="flex items-center gap-4">
                    <button class="text-gray-400 hover:text-white"><i class="fas fa-bell"></i></button>
                    <span class="text-gray-300">Admin</span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>
    
    @yield('scripts')
</body>
</html>
HTML,
            default => $appName . <<<HTML

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= \$appName ?>')</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @yield('head')
</head>
<body class="bg-gray-50 min-h-screen">
    <main class="max-w-7xl mx-auto px-4 py-8">
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>
HTML,
        };
    }
}