<?php

use Zen\Routing\Router;
use App\Http\Controllers\UserController;

$router = app(Router::class);

$router->get('/', 'App\Pages\Home@render');
$router->get('/hello/{name}', fn($req, $name) => response("Hello, {$name}!"));
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

return $router;
