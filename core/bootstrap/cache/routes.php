<?php

return [
    'GET /' => [
        'handler' => 'App\Pages\Welcome@render',
        'name' => null,
    ],
    'GET /hello/{name}' => [
        'handler' => 'App\Http\Controllers\HelloController@greet',
        'name' => null,
    ],
    'GET /users' => [
        'handler' => 'App\Http\Controllers\UserController@index',
        'name' => null,
    ],
    'GET /users/{id}' => [
        'handler' => 'App\Http\Controllers\UserController@show',
        'name' => null,
    ],
    'POST /users' => [
        'handler' => 'App\Http\Controllers\UserController@store',
        'name' => null,
    ],
    'PUT /users/{id}' => [
        'handler' => 'App\Http\Controllers\UserController@update',
        'name' => null,
    ],
    'DELETE /users/{id}' => [
        'handler' => 'App\Http\Controllers\UserController@destroy',
        'name' => null,
    ],
    'GET /api/status' => [
        'handler' => 'App\Http\Controllers\ApiController@status',
        'name' => null,
    ],
    'POST /api/data' => [
        'handler' => 'App\Http\Controllers\ApiController@dataStore',
        'name' => null,
    ],
    'GET /auth/login' => [
        'handler' => 'App\Http\Controllers\AuthController@showLogin',
        'name' => null,
    ],
    'POST /auth/login' => [
        'handler' => 'App\Http\Controllers\AuthController@login',
        'name' => null,
    ],
    'POST /auth/logout' => [
        'handler' => 'App\Http\Controllers\AuthController@logout',
        'name' => null,
    ],
    'GET /auth/register' => [
        'handler' => 'App\Http\Controllers\AuthController@showRegister',
        'name' => null,
    ],
    'POST /auth/register' => [
        'handler' => 'App\Http\Controllers\AuthController@register',
        'name' => null,
    ],
    'POST /ai/chat' => [
        'handler' => 'App\Http\Controllers\AiController@chat',
        'name' => null,
    ],
    'POST /ai/complete' => [
        'handler' => 'App\Http\Controllers\AiController@complete',
        'name' => null,
    ],
];
