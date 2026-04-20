<?php

return [
    'zero_copy' => true,
    'buffer_size' => 65536,
    'socket_buffer_size' => 8192,
    'preload_classes' => true,
    'jit' => true,
    
    'opcache' => [
        'enable' => true,
        'memory' => '256M',
        'interned' => '16M',
        'preload' => true,
        'jit' => 'function',
    ],
    
    'database' => [
        'pool_size' => 50,
        'min_connections' => 10,
        'max_connections' => 100,
        'timeout' => 5,
    ],
    
    'cache' => [
        'levels' => ['memory', 'apc', 'redis'],
        'memory_size' => '256M',
    ],
    
    'rate_limit' => [
        'global' => 100000,
        'api' => 50000,
        'login' => 10,
    ],
    
    'cluster' => [
        'enabled' => false,
        'nodes' => [],
        'load_balancer' => 'round_robin',
    ],
];