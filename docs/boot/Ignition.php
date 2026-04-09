<?php

declare(strict_types=1);

namespace Zenith\Boot;

use Zenith\Container;
use Zenith\Diagnostics\ErrorHandler;

class Ignition
{
    private static function loadAutoloader(): void
    {
        $autoloaderFile = __DIR__ . '/Autoloader.php';
        $helpersFile = dirname(__DIR__) . '/core/Support/helpers.php';
        
        if (!file_exists($autoloaderFile)) {
            throw new \RuntimeException("Autoloader not found");
        }
        
        if (file_exists($helpersFile)) {
            require_once $helpersFile;
        }
        
        require_once $autoloaderFile;
        
        $autoloaderClass = __NAMESPACE__ . '\\Autoloader';
        $autoloader = new $autoloaderClass();
        $autoloader->register();
    }

    public static function fire(): Container
    {
        self::loadAutoloader();

        ErrorHandler::register();

        $container = new Container();

        $configLoader = new ConfigLoader();
        $configLoader->load();
        $container->instance(ConfigLoader::class, $configLoader);

        $routeLoader = new RouteLoader();
        $routeLoader->register($container);
        $container->instance(RouteLoader::class, $routeLoader);

        $container->instance(Container::class, $container);

        self::loadServiceProviders($container);

        return $container;
    }

    protected static function loadServiceProviders(Container $container): void
    {
        $providers = [
            \App\Providers\AppProvider::class,
        ];

        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }

            $instance = new $provider();
            $instance->setContainer($container);
            $instance->register();

            $container->instance($provider, $instance);
        }

        foreach ($providers as $provider) {
            if (!class_exists($provider)) {
                continue;
            }

            $instance = $container->make($provider);
            if (method_exists($instance, 'boot')) {
                $instance->boot();
            }
        }
    }
}
