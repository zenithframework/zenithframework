<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Container;

abstract class Controller
{
    protected Container $container;

    public function __construct()
    {
        $this->container = app(Container::class);
    }

    protected function request(): Request
    {
        return app(Request::class);
    }

    protected function response(array|string $content, int $status = 200): Response
    {
        return new Response($content, $status);
    }

    protected function json(array $data, int $status = 200): Response
    {
        return new Response(json_encode($data), $status, ['Content-Type' => 'application/json']);
    }

    protected function redirect(string $uri): \Zen\Http\Redirect
    {
        return new \Zen\Http\Redirect($uri);
    }

    protected function back(): \Zen\Http\Redirect
    {
        return new \Zen\Http\Redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    protected function view(string $view, array $data = []): Response
    {
        $content = view($view, $data);
        return new Response($content);
    }

    protected function abort(int $code, string $message = ''): void
    {
        $codes = [
            404 => 'Not Found',
            403 => 'Forbidden',
            500 => 'Internal Server Error',
            401 => 'Unauthorized',
            419 => 'Page Expired',
        ];

        http_response_code($code);
        echo $message ?: ($codes[$code] ?? 'Error');
        exit;
    }
}
