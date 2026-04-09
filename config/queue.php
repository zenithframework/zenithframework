<?php

return [
    'default' => 'sync',
    'drivers' => [
        'sync' => [],
        'database' => [
            'table' => 'jobs',
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'database' => 0,
        ],
    ],
];