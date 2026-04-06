<?php

declare(strict_types=1);

namespace Zen\Http;

class Redirect
{
    public static function to(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }

    public static function back(int $status = 302): Response
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return new Response('', $status, ['Location' => $url]);
    }

    public static function route(string $name, array $params = [], int $status = 302): Response
    {
        $url = route($name, $params);
        return new Response('', $status, ['Location' => $url]);
    }

    public static function home(int $status = 302): Response
    {
        return new Response('', $status, ['Location' => '/']);
    }

    public static function away(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}
