<?php

declare(strict_types=1);

namespace Zenith\Support;

class Arr
{
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        $segments = explode('.', $key);
        $data = $array;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public static function set(array &$array, string $key, mixed $value): void
    {
        if (!str_contains($key, '.')) {
            $array[$key] = $value;
            return;
        }

        $segments = explode('.', $key);
        $data = &$array;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }
            $data = &$data[$segment];
        }

        $data[array_shift($segments)] = $value;
    }

    public static function has(array $array, string $key): bool
    {
        if (array_key_exists($key, $array)) {
            return true;
        }

        if (!str_contains($key, '.')) {
            return false;
        }

        $segments = explode('.', $key);
        $data = $array;

        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return false;
            }
            $data = $data[$segment];
        }

        return true;
    }

    public static function except(array $array, array $keys): array
    {
        return array_filter($array, fn($key) => !in_array($key, $keys), ARRAY_FILTER_USE_KEY);
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}
