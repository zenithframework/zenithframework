<?php

declare(strict_types=1);

namespace Zen\Http;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly array $query;
    public readonly array $body;
    public readonly array $headers;
    public readonly ?string $ip;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->query = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->parseHeaders();
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function capture(): self
    {
        return new self();
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $key = str_replace('-', '_', strtoupper($key));
        return $this->headers[$key] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    public function isAjax(): bool
    {
        return $this->header('X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('ACCEPT') ?? '', 'application/json');
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('AUTHORIZATION');

        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    protected function parseBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }

        return $_POST;
    }

    protected function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
        }

        return $headers;
    }
}
