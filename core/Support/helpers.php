<?php

declare(strict_types=1);

use Zen\Container;
use Zen\Boot\ConfigLoader;
use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Http\Redirect;
use Zen\Routing\Router;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        static $container;

        if ($container === null) {
            $container = new Container();
        }

        if ($abstract === null) {
            return $container;
        }

        return $container->make($abstract);
    }
}

if (!function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $loader = app(ConfigLoader::class);

        if ($key === null) {
            return $loader;
        }

        return $loader->get($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): string
    {
        $viewPath = __DIR__ . '/../views/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$template}] not found.");
        }

        extract($data);

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }
}

if (!function_exists('response')) {
    function response(mixed $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $status = 200): Response
    {
        return new Response(
            json_encode(['status' => 'success', 'data' => $data], JSON_THROW_ON_ERROR),
            $status,
            ['Content-Type' => 'application/json']
        );
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return app(Request::class);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $router = app(Router::class);
        return $router->url($name, $params);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}

if (!function_exists('session_token')) {
    function session_token(): string
    {
        return csrf_token();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $baseUrl = rtrim(config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $baseUrl . '/' . $path;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $envFile = __DIR__ . '/../.env';

            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                foreach ($lines as $line) {
                    if (str_starts_with(trim($line), '#')) {
                        continue;
                    }

                    [$envKey, $envValue] = array_pad(explode('=', $line, 2), 2, '');
                    $envValue = trim($envValue, '"\' ');

                    if ($envKey === $key) {
                        $value = $envValue;
                        break;
                    }
                }
            }

            if ($value === false) {
                return $default;
            }
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null', '' => null,
            default => $value,
        };
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        throw new \RuntimeException($message ?: "HTTP {$code} Error", $code);
    }
}

if (!function_exists('dd')) {
    function dd(mixed $value): never
    {
        echo '<pre>';
        var_dump($value);
        echo '</pre>';
        exit(1);
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = new \Zen\Session\Session();
        
        if ($key === null) {
            return $session;
        }
        
        return $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    function auth(?string $guard = null): \Zen\Auth\Auth
    {
        return new \Zen\Auth\Auth();
    }
}
