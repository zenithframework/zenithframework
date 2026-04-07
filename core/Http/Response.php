<?php

declare(strict_types=1);

namespace Zen\Http;

class Response
{
    protected string $content;
    protected int $status;
    protected array $headers;

    public function __construct(
        string $content = '',
        int $status = 200,
        array $headers = []
    ) {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): static
    {
        $headers = $this->headers;
        $headers[$name] = $value;
        return new static($this->content, $this->status, $headers);
    }

    public function withHeaders(array $headers): static
    {
        return new static($this->content, $this->status, array_merge($this->headers, $headers));
    }

    public function withoutHeader(string $name): static
    {
        $headers = $this->headers;
        unset($headers[$name]);
        return new static($this->content, $this->status, $headers);
    }

    public function withContent(string $content): static
    {
        return new static($content, $this->status, $this->headers);
    }

    public function withStatus(int $status): static
    {
        return new static($this->content, $status, $this->headers);
    }

    public function json(mixed $data, int $status = 200): static
    {
        $content = json_encode(['status' => 'success', 'data' => $data], JSON_THROW_ON_ERROR);
        return new static($content, $status, array_merge($this->headers, ['Content-Type' => 'application/json']));
    }

    public function jsonData(mixed $data, int $status = 200): static
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR);
        return new static($content, $status, array_merge($this->headers, ['Content-Type' => 'application/json']));
    }

    public function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, array_merge($this->headers, ['Location' => $url]));
    }

    public function back(int $status = 302): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer, $status);
    }

    public function toRoute(string $name, array $params = [], int $status = 302): static
    {
        $url = app(\Zen\Routing\Router::class)->url($name, $params);
        return $this->redirect($url, $status);
    }

    public function download(string $filePath, ?string $name = null, array $headers = []): static
    {
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        $name = $name ?? basename($filePath);
        $content = file_get_contents($filePath);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        $responseHeaders = array_merge([
            'Content-Type' => $mime,
            'Content-Disposition' => "attachment; filename=\"{$name}\"",
            'Content-Length' => strlen($content),
        ], $headers);

        return new static($content, 200, array_merge($this->headers, $responseHeaders));
    }

    public function stream(callable $callback, int $status = 200, array $headers = []): static
    {
        ob_start();
        $callback();
        $content = ob_get_clean();
        
        return new static($content ?? '', $status, array_merge($this->headers, $headers));
    }

    public function cookie(
        string $name,
        ?string $value = null,
        int $expires = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): static {
        $options = [
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain ?? '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ];

        $cookie = http_build_query($options, '', '; ');
        $headers = $this->headers;
        $headers['Set-Cookie'] = "{$name}={$value}; {$cookie}";

        return new static($this->content, $this->status, $headers);
    }

    public function withCookie(string $name, string $value, int $minutes = 0): static
    {
        return $this->cookie($name, $value, $minutes === 0 ? 0 : time() + ($minutes * 60));
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isRedirect(): bool
    {
        return in_array($this->status, [301, 302, 303, 307, 308]);
    }

    public function isClientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    public function isServerError(): bool
    {
        return $this->status >= 500;
    }

    public static function make(string $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    public static function noContent(int $status = 204): static
    {
        return new static('', $status);
    }

    public static function notFound(?string $message = 'Not Found'): static
    {
        return new static($message, 404);
    }

    public static function unauthorized(?string $message = 'Unauthorized'): static
    {
        return new static($message, 401);
    }

    public static function forbidden(?string $message = 'Forbidden'): static
    {
        return new static($message, 403);
    }

    public static function error(string $message, int $status = 500): static
    {
        return new static($message, $status);
    }
}
