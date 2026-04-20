<?php

return [
    'driver' => 'fpm',
    'host' => '0.0.0.0',
    'port' => 8080,
    'workers' => 4,
    'daemonize' => false,
    'max_request' => 10000,
    'memory_limit' => '128M',
    'preload' => false,
    'jit' => false,
];