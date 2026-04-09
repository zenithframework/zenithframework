<?php

return [
    [
        'method' => 'GET',
        'uri' => '/',
        'handler' => 'App\\Pages\\Welcome@render',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'GET',
        'uri' => '/api/status',
        'handler' => 'App\\Http\\Controllers\\ApiController@status',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'GET',
        'uri' => '/auth/login',
        'handler' => 'App\\Http\\Controllers\\AuthController@showLogin',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'POST',
        'uri' => '/auth/login',
        'handler' => 'App\\Http\\Controllers\\AuthController@login',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'POST',
        'uri' => '/auth/logout',
        'handler' => 'App\\Http\\Controllers\\AuthController@logout',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'GET',
        'uri' => '/auth/register',
        'handler' => 'App\\Http\\Controllers\\AuthController@showRegister',
        'name' => '',
        'middleware' => [],
    ],
    [
        'method' => 'POST',
        'uri' => '/auth/register',
        'handler' => 'App\\Http\\Controllers\\AuthController@register',
        'name' => '',
        'middleware' => [],
    ],
];
