<?php

use Zen\Routing\Router;

$router = app(Router::class);

$router->get('/api/status', fn($req) => json(['status' => 'ok', 'version' => '1.0.0']));
$router->post('/api/data', fn($req) => json(['received' => $req->all()]));

return $router;
