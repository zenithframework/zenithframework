<?php

declare(strict_types=1);

namespace Zenith\Boot;

use Zenith\Container;
use Zenith\Routing\Router;
use Zenith\Routing\Route;
use Zenith\Routing\RouteGroup;

class RouteLoader
{
    protected array $routeFiles = [
        'Web' => '/',
        'Api' => '/api',
        'Auth' => '/auth',
        'Ai' => '/ai',
    ];

    public function register(Container $container): void
    {
        $routesDir = __DIR__ . '/../routes/';
        $cacheDir = __DIR__ . '/cache';

        if (!$container->has(Router::class)) {
            $container->singleton(Router::class, fn() => new Router());
        }

        $router = $container->make(Router::class);

        $cachedRoutes = $this->loadFromCache($cacheDir);
        
        if ($cachedRoutes !== null) {
            foreach ($cachedRoutes as $routeData) {
                $route = new Route(
                    $routeData['method'],
                    $routeData['uri'],
                    $routeData['handler']
                );
                
                if (isset($routeData['name'])) {
                    $route->setName($routeData['name']);
                }
                
                if (!empty($routeData['middleware'])) {
                    $route->setMiddleware($routeData['middleware']);
                }
                
                $router->getRoutes()[] = $route;
            }
        } else {
            foreach ($this->routeFiles as $name => $prefix) {
                $file = $routesDir . $name . '.php';

                if (file_exists($file)) {
                    $this->loadRouteFile($router, $file, $prefix);
                }
            }

            $this->saveToCache($cacheDir, $router->getRoutes());
        }

        $container->instance(Router::class, $router);
    }

    protected function loadRouteFile(Router $router, string $file, string $prefix): void
    {
        $router->setCurrentGroup(new \Zenith\Routing\RouteGroupContext($prefix));
        
        require $file;
        
        $router->setCurrentGroup(null);
    }

    protected function loadFromCache(string $cacheDir): ?array
    {
        $cacheFile = $cacheDir . '/routes.php';
        
        if (!file_exists($cacheFile)) {
            return null;
        }

        $env = getenv('APP_ENV') ?: 'development';
        
        if ($env !== 'production') {
            return null;
        }

        $routes = require $cacheFile;
        
        return is_array($routes) ? $routes : null;
    }

    protected function saveToCache(string $cacheDir, array $routes): void
    {
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cacheFile = $cacheDir . '/routes.php';
        
        $data = "<?php\n\nreturn [\n";
        
        foreach ($routes as $route) {
            $method = $route->getMethod();
            $uri = addslashes($route->getUri());
            $name = addslashes($route->getName() ?? '');
            
            $handler = $route->getHandler();
            if (is_array($handler)) {
                $handlerStr = addslashes($handler[0] . '@' . $handler[1]);
            } else {
                $handlerStr = 'Closure';
            }
            
            $middleware = $route->getMiddleware();
            $middlewareStr = json_encode($middleware);
            
            $data .= "    [\n";
            $data .= "        'method' => '{$method}',\n";
            $data .= "        'uri' => '{$uri}',\n";
            $data .= "        'handler' => '{$handlerStr}',\n";
            $data .= "        'name' => '{$name}',\n";
            $data .= "        'middleware' => {$middlewareStr},\n";
            $data .= "    ],\n";
        }
        
        $data .= "];\n";
        
        file_put_contents($cacheFile, $data);
    }

    public function clearCache(): void
    {
        $cacheDir = __DIR__ . '/cache';
        $cacheFile = $cacheDir . '/routes.php';
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
