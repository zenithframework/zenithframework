<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'root' => dirname(__DIR__) . '/storage',
        ],
        'public' => [
            'root' => dirname(__DIR__) . '/storage/app/public',
            'url' => '/storage',
        ],
        's3' => [
            'key' => '',
            'secret' => '',
            'region' => 'us-east-1',
            'bucket' => '',
            'endpoint' => '',
        ],
        'ftp' => [
            'host' => 'ftp.example.com',
            'port' => 21,
            'username' => '',
            'password' => '',
            'root' => '/',
        ],
    ],
];