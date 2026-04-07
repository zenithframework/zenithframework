<?php

declare(strict_types=1);

namespace App\Providers;

use Zen\Boot\ServiceProvider;
use Zen\Database\QueryBuilder;
use Zen\Session\Session;
use Zen\Cache\Cache;
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
    }
}