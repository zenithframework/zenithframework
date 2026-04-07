<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\PostController;
use App\Pages\Welcome;
use App\Pages\Home;

$router->get('/', [Welcome::class, 'render']);
$router->get('/test', fn() => view('pages.test-page', [
    'showMessage' => true,
    'items' => ['Item 1', 'Item 2', 'Item 3'],
    'data' => ['key' => 'value']
]));
$router->get('/home', [Home::class, 'render']);
$router->get('/hello/{name}', [HelloController::class, 'greet']);
$router->get('/users', [UserController::class, 'index']);
$router->get('/users/{id}', [UserController::class, 'show']);
$router->post('/users', [UserController::class, 'store']);
$router->put('/users/{id}', [UserController::class, 'update']);
$router->delete('/users/{id}', [UserController::class, 'destroy']);

$router->get('/posts', [PostController::class, 'index']);
$router->get('/posts/{id}', [PostController::class, 'show']);
$router->post('/posts', [PostController::class, 'store']);
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);

return $router;