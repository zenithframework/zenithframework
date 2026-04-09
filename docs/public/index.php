<?php

define('ZEN_START', microtime(true));

require_once __DIR__ . '/../boot/Ignition.php';

$container = Zen\Boot\Ignition::fire();

$router = $container->make(\Zenith\Routing\Router::class);

foreach (['Web', 'Api', 'Auth', 'Ai'] as $routeFile) {
    $file = __DIR__ . '/../routes/' . $routeFile . '.php';
    if (file_exists($file)) {
        require $file;
    }
}

$request = \Zenith\Http\Request::capture();
$response = $router->match($request);

if ($response === null) {
    http_response_code(404);
    echo "404 Not Found";
    exit(1);
}

$response->send();