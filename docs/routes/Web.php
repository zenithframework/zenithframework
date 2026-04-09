<?php

declare(strict_types=1);

use App\Pages\Welcome;

$router->get('/', [Welcome::class, 'render']);

return $router;