<?php

declare(strict_types=1);

namespace Zen\Boot;

use Zen\Container;
use Zen\Diagnostics\ErrorHandler;

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
        $routeLoader->register();
        $container->instance(RouteLoader::class, $routeLoader);

        $container->instance(Container::class, $container);

        return $container;
    }
}
