<?php

declare(strict_types=1);

namespace Zenith\Http;

class IsrResponse extends Response
{
    protected array $isrMetadata = [];

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
    }

    public static function fromJson(array $data): self
    {
        $response = new self(
            $data['html'] ?? '',
            $data['status'] ?? 200,
            $data['headers'] ?? []
        );
        
        $response->isrMetadata = $data['isr_metadata'] ?? [];
        
        return $response;
    }

    public function toJson(): array
    {
        return [
            'html' => $this->content,
            'status' => $this->status,
            'headers' => $this->headers,
            'isr_metadata' => $this->isrMetadata,
        ];
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setMetadata(string $key, mixed $value): self
    {
        $this->isrMetadata[$key] = $value;
        return $this;
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->isrMetadata[$key] ?? $default;
    }
}
