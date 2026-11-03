<?php

declare(strict_types=1);

namespace App\Providers;

use Zenith\Boot\ServiceProvider;
use Zenith\Database\QueryBuilder;
use Zenith\Session\Session;
use Zenith\Cache\Cache;
use App\Services\AuthService;

class AppProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app()->singleton(AuthService::class, fn() => new AuthService());
        
        $this->app()->bind(QueryBuilder::class, fn() => new QueryBuilder());
        
        $this->app()->bind(Session::class, fn() => new Session());
        
        $this->app()->bind(Cache::class, fn() => new Cache());
    }

    public function boot(): void
    {
        // Store the CSRF token under the exact key the Csrf middleware and
        // SanitizationService read (`_token`). Previously this wrote
        // `csrf_token`, so validation always failed or diverged.
        if (!session()->has('_token')) {
            session()->set('_token', csrf_token());
        }
        
        // Register SSR/ISR services
        SsrServiceProvider::boot();
    }
}