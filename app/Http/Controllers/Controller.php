<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\Container;
use Zenith\Validation\Validator;
use Closure;

abstract class Controller
{
    protected Container $container;
    protected array $middleware = [];

    public function __construct()
    {
        $this->container = app(Container::class);
    }

    /**
     * Register middleware for the controller.
     *
     * @param string|Closure|array|string $middleware
     * @param array $options
     * @return void
     */
    public function middleware(mixed $middleware, array $options = []): void
    {
        $this->middleware[] = [
            'middleware' => $middleware,
            'options' => $options,
        ];
    }

    /**
     * Get the registered middleware.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Get the current request instance.
     *
     * @return Request
     */
    protected function request(): Request
    {
        return app(Request::class);
    }

    /**
     * Create a new response instance.
     *
     * @param array|string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function response(array|string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Create a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return Response
     */
    protected function json(mixed $data, int $status = 200, array $headers = [], int $options = 0): Response
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR | $options);
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new Response($content, $status, $headers);
    }

    /**
     * Create a JSONP response.
     *
     * @param string $callback
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function jsonp(string $callback, mixed $data, int $status = 200, array $headers = []): Response
    {
        $content = sprintf('%s(%s)', $callback, json_encode($data, JSON_THROW_ON_ERROR));
        $headers = array_merge(['Content-Type' => 'application/javascript'], $headers);
        return new Response($content, $status, $headers);
    }

    /**
     * Redirect to a URL.
     *
     * @param string $uri
     * @param int $status
     * @param array $headers
     * @return \Zenith\Http\Redirect
     */
    protected function redirect(string $uri, int $status = 302, array $headers = []): \Zenith\Http\Redirect
    {
        return new \Zenith\Http\Redirect($uri, $status, $headers);
    }

    /**
     * Redirect back to the previous location.
     *
     * @param mixed $default
     * @param int $status
     * @param array $headers
     * @return \Zenith\Http\Redirect
     */
    protected function back(mixed $default = '/', int $status = 302, array $headers = []): \Zenith\Http\Redirect
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        return new \Zenith\Http\Redirect($referer, $status, $headers);
    }

    /**
     * Redirect to a named route.
     *
     * @param string $route
     * @param array $parameters
     * @param int $status
     * @param array $headers
     * @return \Zenith\Http\Redirect
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302, array $headers = []): \Zenith\Http\Redirect
    {
        $uri = route($route, $parameters);
        return $this->redirect($uri, $status, $headers);
    }

    /**
     * Redirect to an action.
     *
     * @param string|array $action
     * @param array $parameters
     * @param int $status
     * @param array $headers
     * @return \Zenith\Http\Redirect
     */
    protected function redirectToAction(string|array $action, array $parameters = [], int $status = 302, array $headers = []): \Zenith\Http\Redirect
    {
        $uri = action($action, $parameters);
        return $this->redirect($uri, $status, $headers);
    }

    /**
     * Return a view response.
     *
     * @param string $view
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function view(string $view, array $data = [], int $status = 200, array $headers = []): Response
    {
        $content = view($view, $data);
        return new Response($content, $status, $headers);
    }

    /**
     * Download a file.
     *
     * @param string $path
     * @param string|null $name
     * @param array $headers
     * @return Response
     */
    protected function download(string $path, ?string $name = null, array $headers = []): Response
    {
        if (!file_exists($path)) {
            $this->abort(404, 'File not found');
        }

        $name = $name ?? basename($path);
        $headers = array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Content-Length' => filesize($path),
        ], $headers);

        return new Response(file_get_contents($path), 200, $headers);
    }

    /**
     * Return a file response.
     *
     * @param string $path
     * @param array $headers
     * @return Response
     */
    protected function file(string $path, array $headers = []): Response
    {
        if (!file_exists($path)) {
            $this->abort(404, 'File not found');
        }

        $mimeType = mime_content_type($path);
        $headers = array_merge([
            'Content-Type' => $mime,
            'Content-Length' => filesize($path),
        ], $headers);

        return new Response(file_get_contents($path), 200, $headers);
    }

    /**
     * Create a validation response.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return array|Response
     */
    protected function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): array|Response
    {
        $validator = Validator::make($data, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            return $this->back()->withInput()->withErrors($validator->errors());
        }

        return $validator->validated();
    }

    /**
     * Abort with an HTTP error.
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     * @throws \RuntimeException
     */
    protected function abort(int $code, string $message = '', array $headers = []): void
    {
        $codes = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => "I'm a teapot",
            419 => 'Page Expired',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
        ];

        http_response_code($code);

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        if (request()->expectsJson()) {
            echo json_encode([
                'message' => $message ?: ($codes[$code] ?? 'Error'),
                'status' => $code,
            ]);
        } else {
            echo $message ?: ($codes[$code] ?? 'Error');
        }

        exit;
    }

    /**
     * Abort if the condition is not met.
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @return void
     */
    protected function abortIf(bool $condition, int $code, string $message = ''): void
    {
        if ($condition) {
            $this->abort($code, $message);
        }
    }

    /**
     * Abort unless the condition is met.
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @return void
     */
    protected function abortUnless(bool $condition, int $code, string $message = ''): void
    {
        if (!$condition) {
            $this->abort($code, $message);
        }
    }

    /**
     * Authorize the current user.
     *
     * @param bool $condition
     * @param string $message
     * @return void
     */
    protected function authorize(bool $condition, string $message = 'This action is unauthorized.'): void
    {
        if (!$condition) {
            $this->abort(403, $message);
        }
    }

    /**
     * Authorize using Gate ability.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return void
     */
    protected function authorizeAbility(string $ability, mixed ...$arguments): void
    {
        gate()->authorize($ability, ...$arguments);
    }

    /**
     * Check if user has ability via Gate.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    protected function can(string $ability, mixed ...$arguments): bool
    {
        return gate()->allows($ability, ...$arguments);
    }

    /**
     * Check if user cannot do ability via Gate.
     *
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    protected function cannot(string $ability, mixed ...$arguments): bool
    {
        return gate()->denies($ability, ...$arguments);
    }

    /**
     * Get the authenticated user.
     *
     * @return mixed
     */
    protected function user(): mixed
    {
        return auth()->user();
    }

    /**
     * Check if the user is authenticated.
     *
     * @return bool
     */
    protected function authCheck(): bool
    {
        return auth()->check();
    }

    /**
     * Get the current container instance.
     *
     * @return Container
     */
    protected function container(): Container
    {
        return $this->container;
    }

    /**
     * Dynamically call methods on the container.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        // Try to resolve from container
        if ($this->container->has($method)) {
            return $this->container->make($method);
        }

        throw new \BadMethodCallException("Call to undefined method " . static::class . "::{$method}()");
    }
}
