<?php

declare(strict_types=1);

namespace Zen\UI;

abstract class Component
{
    protected array $props = [];
    protected array $slots = [];

    public function __construct(array $props = [])
    {
        $this->props = $props;
    }

    abstract public function render(): string;

    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);
        return $this;
    }

    public function getProp(string $key, mixed $default = null): mixed
    {
        return $this->props[$key] ?? $default;
    }

    public function hasProp(string $key): bool
    {
        return isset($this->props[$key]);
    }

    public function slot(string $name, string $content): static
    {
        $this->slots[$name] = $content;
        return $this;
    }

    public function getSlot(string $name): string
    {
        return $this->slots[$name] ?? '';
    }

    public function hasSlot(string $name): bool
    {
        return isset($this->slots[$name]);
    }

    protected function e(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    protected function attrs(array $attrs): string
    {
        $html = [];

        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $html[] = $key;
            } else {
                $html[] = $key . '="' . $this->e($value) . '"';
            }
        }

        return implode(' ', $html);
    }
}
