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