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
        session()->set('csrf_token', csrf_token());
        
        // Register SSR/ISR services
        SsrServiceProvider::boot();
    }
}