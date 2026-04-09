<?php

declare(strict_types=1);

use App\Http\Controllers\UserController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SsrExampleController;
use App\Http\Controllers\IsrExampleController;
use App\Pages\Welcome;
use App\Pages\Home;

$router->get('/', [Welcome::class, 'render']);
$router->get('/demo', fn() => view('pages.demo-ssr-isr-sse'));
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

// SSR (Server-Side Rendering) Examples
$router->get('/ssr', [SsrExampleController::class, 'home']);
$router->get('/ssr/blog', [SsrExampleController::class, 'blog']);
$router->get('/ssr/prerender', [SsrExampleController::class, 'prerender']);
$router->get('/ssr/dynamic', [SsrExampleController::class, 'dynamic']);

// ISR (Incremental Static Regeneration) Examples
$router->get('/isr/product/{id}', [IsrExampleController::class, 'product']);
$router->get('/isr/article/{slug}', [IsrExampleController::class, 'article']);
$router->post('/isr/revalidate', [IsrExampleController::class, 'revalidate']);
$router->get('/isr/warm', [IsrExampleController::class, 'warmCache']);
$router->get('/isr/stats', [IsrExampleController::class, 'stats']);

return $router;