<?php

declare(strict_types=1);

namespace App\Providers;

use Zenith\Http\SsrEngine;
use Zenith\Http\IsrEngine;

class SsrServiceProvider
{
    public function register(): void
    {
        // Lazy configuration - only load when engines are actually used
        static $registered = false;
        
        if ($registered) {
            return;
        }
        
        $registered = true;
    }

    public static function boot(): void
    {
        static $booted = false;
        
        if ($booted) {
            return;
        }
        
        $booted = true;

        try {
            // Configure SSR Engine
            $ssrConfig = config('ssr.ssr', []);
            SsrEngine::configure([
                'enabled' => $ssrConfig['enabled'] ?? true,
                'cache_path' => $ssrConfig['cache_path'] ?? 'storage/ssr/',
                'ttl' => $ssrConfig['ttl'] ?? 3600,
            ]);

            // Configure ISR Engine
            $isrConfig = config('ssr.isr', []);
            IsrEngine::configure([
                'enabled' => $isrConfig['enabled'] ?? true,
                'cache_path' => $isrConfig['cache_path'] ?? 'storage/isr/',
                'ttl' => $isrConfig['ttl'] ?? 60,
                'background_revalidation' => $isrConfig['background_revalidation'] ?? true,
            ]);

            // Register ISR revalidation callbacks
            $callbacks = $isrConfig['callbacks'] ?? [];
            foreach ($callbacks as $pattern => $callbackList) {
                foreach ($callbackList as $callback) {
                    IsrEngine::onRevalidate($pattern, $callback);
                }
            }
        } catch (\Exception $e) {
            // Fail gracefully - use defaults
            SsrEngine::configure([
                'enabled' => true,
                'cache_path' => 'storage/ssr/',
                'ttl' => 3600,
            ]);
            
            IsrEngine::configure([
                'enabled' => true,
                'cache_path' => 'storage/isr/',
                'ttl' => 60,
                'background_revalidation' => true,
            ]);
        }
    }
}
