<?php

return [
    'name' => env('APP_NAME', 'Zenith Framework'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    // Never generate a random key at runtime: an ephemeral key makes encrypted
    // data and signed cookies unverifiable across requests/processes. The app
    // must ship an explicit APP_KEY.
    'key' => env('APP_KEY'),
    'cipher' => 'aes-256-gcm',
    'providers' => [
        \App\Providers\AppProvider::class,
    ],
];
