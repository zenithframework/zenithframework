<?php

return [
    'name' => 'Zen App',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost:8080',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY', 'base64:' . base64_encode(random_bytes(32))),
    'cipher' => 'AES-256-CBC',
    'providers' => [
        \App\Providers\AppProvider::class,
    ],
];