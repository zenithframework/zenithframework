<?php

declare(strict_types=1);

use App\Http\Controllers\AiController;

$router->post('/chat', [AiController::class, 'chat']);
$router->post('/complete', [AiController::class, 'complete']);

return $router;