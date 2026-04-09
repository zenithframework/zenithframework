<?php

return array (
  'app' => 
  array (
    'name' => 'Zen App',
    'env' => 'development',
    'debug' => true,
    'url' => 'http://localhost:8080',
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => 'base64:LJSuY8F/Ib+D7szA9Ap/VO8sRjRB7pSEGALMCEaLp1A=',
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
        'database' => 'C:\\Users\\shamimstack\\Desktop\\herd\\zen\\docs\\database\\database.sqlite',
        'prefix' => '',
      ),
    ),
  ),
);
