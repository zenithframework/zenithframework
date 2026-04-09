<?php

return [
    'default' => 'log',
    'drivers' => [
        'log' => [],
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'timeout' => 30,
        ],
        'sendmail' => [
            'binary' => '/usr/sbin/sendmail',
        ],
    ],
    'from' => [
        'address' => 'noreply@localhost',
        'name' => 'Zenith Framework',
    ],
];