<?php

declare(strict_types=1);

define('ZEN_START', microtime(true));

require_once __DIR__ . '/../boot/Ignition.php';

use Zen\Boot\Ignition;
use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Routing\Router;
use Zen\Session\Session;
use Zen\Auth\Auth;

$container = Ignition::fire();

$request = Request::capture();

Session::start();

Auth::loadFromSession();

$router = $container->make(Router::class);

$response = $container->make(\Zen\Boot\Engine::class)->handle($request);

$response->send();
