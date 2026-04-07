<?php

return [
    'driver' => 'file',
    'drivers' => [
        'file' => [
            'path' => dirname(__DIR__) . '/storage/rate_limits',
        ],
        'database' => [
            'table' => 'rate_limits',
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'database' => 1,
        ],
    ],
    'default' => [
        'max_attempts' => 60,
        'decay_seconds' => 60,
    ],
];