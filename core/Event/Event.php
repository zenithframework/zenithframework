<?php

declare(strict_types=1);

namespace Zenith\Event;

class Event
{
    public string $name;
    public array $payload;
    public bool $propagationStopped = false;

    public function __construct(string $name, array $payload = [])
    {
        $this->name = $name;
        $this->payload = $payload;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }
}