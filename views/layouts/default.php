<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Zen Framework'; ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-white shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="text-xl font-bold text-blue-600">Zen Framework</a>
            <div class="flex items-center space-x-4">
                <?php if (auth()->check()): ?>
                    <span class="text-gray-600"><?php echo auth()->user()->name; ?></span>
                    <form method="POST" action="/auth/logout">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="text-red-600 hover:text-red-700">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="/auth/login" class="text-gray-600 hover:text-gray-900">Login</a>
                    <a href="/auth/register" class="text-blue-600 hover:text-blue-700">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php echo $content ?? ''; ?>
    </main>

    <footer class="bg-white border-t mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-500">
            Powered by Zen Framework
        </div>
    </footer>
</body>
</html>
