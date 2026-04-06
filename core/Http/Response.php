<?php

declare(strict_types=1);

namespace Zen\Http;

class Response
{
    public function __construct(
        public readonly string $content = '',
        public readonly int $status = 200,
        public readonly array $headers = []
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;
        return new self($this->content, $this->status, $headers);
    }

    public function withContent(string $content): self
    {
        return new self($content, $this->status, $this->headers);
    }

    public function withStatus(int $status): self
    {
        return new self($this->content, $status, $this->headers);
    }

    public function json(mixed $data, int $status = 200): self
    {
        $content = json_encode(['status' => 'success', 'data' => $data], JSON_THROW_ON_ERROR);
        return new self($content, $status, array_merge($this->headers, ['Content-Type' => 'application/json']));
    }

    public function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, array_merge($this->headers, ['Location' => $url]));
    }
}
