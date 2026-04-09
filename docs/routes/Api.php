<?php

declare(strict_types=1);

use App\Http\Controllers\ApiController;

$router->get('/status', [ApiController::class, 'status']);

return $router;