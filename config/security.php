<?php

return [
    'enabled' => true,
    
    'ddos_protection' => [
        'enabled' => true,
        'max_requests_per_minute' => 1000,
        'challenge_threshold' => 60,
        'block_threshold' => 80,
    ],
    
    'waf' => [
        'enabled' => true,
        'log_only' => false,
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'login' => ['max' => 5, 'window' => 300],
        'api' => ['max' => 100, 'window' => 60],
        'global' => ['max' => 1000, 'window' => 60],
    ],
    
    'ip_blocking' => [
        'enabled' => true,
        'auto_block' => true,
        'block_duration' => 3600,
        'block_threshold' => 10,
    ],
    
    'geo_blocking' => [
        'enabled' => false,
        'blocked_countries' => [],
    ],
    
    'asn_blocking' => [
        'enabled' => false,
        'blocked_asn' => [],
    ],
    
    'session' => [
        'name' => 'zen_session',
        'lifetime' => 7200,
        'regenerate' => true,
        'httponly' => true,
        'secure' => false,
        'samesite' => 'Strict',
        'verify_ip' => true,
        'verify_user_agent' => true,
        'timeout' => true,
    ],
    
    'csrf' => [
        'enabled' => true,
        'token_length' => 32,
    ],
    
    'password' => [
        'algo' => PASSWORD_BCRYPT,
        'cost' => 12,
    ],
    
    'two_factor' => [
        'enabled' => false,
    ],
    
    'alerts' => [
        'email' => null,
        'slack' => null,
        'telegram' => null,
    ],
    
    'request_limits' => [
        'max_memory' => '128M',
        'max_time' => 30,
        'max_post_size' => '10M',
        'max_upload_size' => '10M',
        'max_fields' => 100,
        'max_input_vars' => 1000,
    ],
    
    'attack_response' => [
        'sql_injection' => ['action' => 'block', 'log' => true],
        'xss_attempt' => ['action' => 'sanitize', 'log' => true],
        'ddos_detected' => ['action' => 'challenge', 'block_duration' => 300],
        'brute_force' => ['action' => 'lock', 'attempts' => 5, 'lock_duration' => 900],
    ],
];