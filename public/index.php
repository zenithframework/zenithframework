<?php

declare(strict_types=1);

define('ZEN_START', microtime(true));

require_once __DIR__ . '/../boot/Ignition.php';

use Zenith\Boot\Ignition;
use Zenith\Boot\Engine;
use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Routing\Router;
use Zenith\Session\Session;
use Zenith\Auth\Auth;

$container = Ignition::fire();

$request = Request::capture();

Session::start();

Auth::loadFromSession();

$router = $container->make(Router::class);

$response = $container->make(Engine::class)->handle($request);

$response->send();
