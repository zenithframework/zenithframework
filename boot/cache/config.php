<?php

return array (
  'app' => 
  array (
    'name' => 'Zenith App',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost:8080',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => 'base64:QT8ZjM1pxEhPpWlW/CdF7CkfGXGt47lAPUpCiiWeg/Q=',
    'cipher' => 'AES-256-CBC',
    'providers' => 
    array (
      0 => 'App\\Providers\\AppProvider',
    ),
  ),
  'database' => 
  array (
    'default' => 'sqlite',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'database' => 'C:\\Users\\shamimstack\\Desktop\\herd\\Zenith\docs/database/database.sqlite',
        'prefix' => '',
      ),
    ),
  ),
);
