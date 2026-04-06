<?php

use Zen\Routing\Router;

$router = app(Router::class);

$router->post('/ai/chat', fn($req) => json(['response' => 'AI response']));
$router->post('/ai/complete', fn($req) => json(['completion' => 'AI completion']));

return $router;
