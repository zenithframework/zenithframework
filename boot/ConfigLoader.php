<?php

declare(strict_types=1);

namespace Zen\Boot;

class ConfigLoader
{
    protected array $config = [];

    public function load(): void
    {
        $configDir = __DIR__ . '/../config/';

        if (!is_dir($configDir)) {
            return;
        }

        $files = glob($configDir . '*.php');

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $this->config[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $data = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $data = &$this->config;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data = &$data[$segment];
        }

        $data[array_shift($segments)] = $value;
    }

    public function all(): array
    {
        return $this->config;
    }
}
