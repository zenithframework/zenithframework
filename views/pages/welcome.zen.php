<?php // Zen Template: Welcome Page with Tailwind + Alpine.js (Custom Dark Theme) ?>
<?php
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Zen Framework - Welcome</title>
    <link rel="stylesheet" href="/assets/css/app.css" />
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('theme', () => ({
                isDark: true,
                init() {
                    const saved = localStorage.getItem('zen-theme');
                    this.isDark = saved ? saved === 'dark' : true;
                    this.applyTheme();
                },
                applyTheme() {
                    document.documentElement.classList.toggle('dark', this.isDark);
                },
                toggle() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('zen-theme', this.isDark ? 'dark' : 'light');
                    this.applyTheme();
                }
            }));
        });
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-zen-800 to-zen-900 text-text-primary">
    <div x-data="theme" x-init="init()">
        <!-- Navigation -->
        <nav class="flex items-center justify-between px-6 py-4 bg-zen-700/80 backdrop-blur-sm border-b border-gray-800/50 sticky top-0 z-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-accent flex items-center justify-center">
                    <span class="text-white font-bold text-lg">Z</span>
                </div>
                <span class="text-xl font-semibold text-text-primary">Zen Framework</span>
            </div>
            <button @click="toggle()" class="px-4 py-2 rounded-lg bg-zen-600 hover:bg-zen-500 text-text-secondary transition-colors">
                <span x-text="isDark ? '☀️ Light' : '🌙 Dark'"></span>
            </button>
        </nav>

        <!-- Hero Section -->
        <main class="max-w-5xl mx-auto px-6 py-16">
            <section class="text-center mb-16">
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-accent mb-6 shadow-lg shadow-accent/30">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h1 class="text-4xl md:text-6xl font-extrabold text-text-primary mb-4">
                    <?= htmlspecialchars($greeting) ?>, Welcome to Zen
                </h1>
                <p class="text-xl text-text-muted max-w-2xl mx-auto">
                    Clean, Fast, Modern PHP Framework for building powerful web applications
                </p>
            </section>

            <!-- Features Grid -->
            <section class="grid md:grid-cols-3 gap-6 mb-12">
                <div class="card hover:border-accent/50 transition-colors group">
                    <div class="w-12 h-12 rounded-lg bg-green-500/20 flex items-center justify-center mb-4 group-hover:bg-green-500/30 transition-colors">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-text-primary mb-2">High Performance</h3>
                    <p class="text-text-muted text-sm">Built for speed with optimized routing, caching, and modern PHP 8.4+ features</p>
                </div>

                <div class="card hover:border-accent/50 transition-colors group">
                    <div class="w-12 h-12 rounded-lg bg-purple-500/20 flex items-center justify-center mb-4 group-hover:bg-purple-500/30 transition-colors">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-text-primary mb-2">Enterprise Security</h3>
                    <p class="text-text-muted text-sm">Built-in protection with WAF, DDoS protection, rate limiting, and more</p>
                </div>

                <div class="card hover:border-accent/50 transition-colors group">
                    <div class="w-12 h-12 rounded-lg bg-orange-500/20 flex items-center justify-center mb-4 group-hover:bg-orange-500/30 transition-colors">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-text-primary mb-2">Modular Architecture</h3>
                    <p class="text-text-muted text-sm">Clean separation of concerns with services, events, queues, and mail</p>
                </div>
            </section>

            <!-- Quick Start Code Block -->
            <section class="card mb-12">
                <h2 class="text-xl font-semibold text-text-primary mb-4">Quick Start</h2>
                <div class="bg-gray-950 rounded-lg p-4 font-mono text-sm overflow-x-auto">
<pre class="text-text-secondary"><code><span class="text-green-400"># Serve your application</span>
php zen serve

<span class="text-green-400"># Create a new controller</span>
php zen make:controller PostController

<span class="text-green-400"># Run migrations</span>
php zen migrate

<span class="text-green-400"># Check routes</span>
php zen route:list</code></pre>
                </div>
            </section>

            <!-- Links Grid -->
            <section class="grid md:grid-cols-2 gap-6">
                <a href="/api/status" class="card hover:border-accent transition-colors group cursor-pointer">
                    <h3 class="text-lg font-semibold text-text-primary group-hover:text-accent transition-colors">API Status</h3>
                    <p class="text-text-muted text-sm mt-1">Check API health and endpoints</p>
                </a>
                <a href="/docs" class="card hover:border-accent transition-colors group cursor-pointer">
                    <h3 class="text-lg font-semibold text-text-primary group-hover:text-accent transition-colors">Documentation</h3>
                    <p class="text-text-muted text-sm mt-1">Learn how to build with Zen</p>
                </a>
            </section>

            <!-- Footer -->
            <footer class="mt-16 text-center text-text-muted text-sm">
                <p>Zen Framework v1.0.0 | Built with PHP & Tailwind</p>
            </footer>
        </main>
    </div>
</body>
</html>
