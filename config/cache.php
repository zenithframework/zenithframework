<?php

return [
    'default' => 'file',
    
    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/cache',
        ],
        
        'array' => [
            'driver' => 'array',
        ],
        
        'apc' => [
            'driver' => 'apc',
            'prefix' => 'zen_',
        ],
        
        'redis' => [
            'driver' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
    ],
    
    'ttl' => 3600,
];
