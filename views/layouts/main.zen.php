<?php $appName = config('app.name', 'Zen'); ?>
<!DOCTYPE html>
<html lang="{{ $lang ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '<?= $appName ?>')</title>
    
    <!-- SEO -->
    <meta name="description" content="@yield('description', '<?= $appName ?> - Clean, Fast, Modern PHP')">
    <meta name="keywords" content="@yield('keywords', '')">
    <meta name="author" content="@yield('author', '')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og:title', '')">
    <meta property="og:description" content="@yield('og:description', '')">
    <meta property="og:image" content="@yield('og:image', '')">
    <meta property="og:url" content="@yield('og:url', '')">
    <meta property="og:type" content="@yield('og:type', 'website')">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="@yield('twitter:card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter:title', '')">
    <meta name="twitter:description" content="@yield('twitter:description', '')">
    <meta name="twitter:image" content="@yield('twitter:image', '')">
    
    <link rel="canonical" href="@yield('canonical', '')">
    <link rel="icon" type="image/x-icon" href="@yield('favicon', 'favicon.ico')">
    
    @yield('head')
</head>
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-900">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 bg-blue-600 text-white px-4 py-2 rounded">
        Skip to content
    </a>
    
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between" aria-label="Main navigation">
            <div class="flex items-center gap-3">
                <a href="/" class="text-xl font-bold text-gray-900"><?= $appName ?></a>
            </div>
            <ul class="hidden md:flex items-center gap-6">
                @yield('nav')
            </ul>
            <button class="md:hidden p-2" aria-label="Toggle menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
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
        <div class="container mx-auto px-4 py-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white font-semibold mb-4"><?= $appName ?></h3>
                    <p class="text-sm">Clean, Fast, Modern PHP Framework</p>
                </div>
                <div>
                    <h4 class="text-white font-medium mb-4">Links</h4>
                    @yield('footer-links')
                </div>
                <div>
                    <h4 class="text-white font-medium mb-4">Contact</h4>
                    <p class="text-sm">@yield('footer-contact', '')</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm">
                &copy; {{ date('Y') }} <?= $appName ?>. All rights reserved.
            </div>
        </div>
    </footer>
    
    @yield('scripts')
</body>
</html>