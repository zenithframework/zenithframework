<?php $appName = config('app.name', 'Zen'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= $appName ?>')</title>
    <meta name="description" content="@yield('description', '')">
    @yield('head')
</head>
<body class="min-h-screen flex flex-col">
    <main class="flex-1">
        @yield('content')
    </main>
    <footer class="py-4 text-center text-gray-500 text-sm">
        &copy; {{ date('Y') }} <?= $appName ?>
    </footer>
    @yield('scripts')
</body>
</html>