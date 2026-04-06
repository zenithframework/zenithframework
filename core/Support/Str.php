<?php

declare(strict_types=1);

namespace Zen\Support;

class Str
{
    public static function slug(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s-]/', '', $value);
        $value = preg_replace('/[\s-]+/', '-', $value);
        return trim($value, '-');
    }

    public static function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    public static function camel(string $value): string
    {
        return lcfirst(static::studly($value));
    }

    public static function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }

    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    public static function plural(string $value): string
    {
        $irregular = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'foot' => 'feet',
            'tooth' => 'teeth',
            'mouse' => 'mice',
            'ox' => 'oxen',
        ];

        $lower = strtolower($value);

        if (isset($irregular[$lower])) {
            $suffix = substr($value, -strlen($lower));
            return substr($value, 0, -strlen($lower)) . $irregular[$lower];
        }

        if (str_ends_with($lower, 's') || str_ends_with($lower, 'x') || str_ends_with($lower, 'z') || str_ends_with($lower, 'ch') || str_ends_with($lower, 'sh')) {
            return $value . 'es';
        }

        if (str_ends_with($lower, 'y') && !in_array(substr($lower, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($value, 0, -1) . 'ies';
        }

        if (str_ends_with($lower, 'fe')) {
            return substr($value, 0, -2) . 'ves';
        }

        if (str_ends_with($lower, 'f') && !str_ends_with($lower, 'ff')) {
            return substr($value, 0, -1) . 'ves';
        }

        return $value . 's';
    }

    public static function singular(string $value): string
    {
        $irregular = [
            'people' => 'person',
            'men' => 'man',
            'women' => 'woman',
            'children' => 'child',
            'feet' => 'foot',
            'teeth' => 'tooth',
            'mice' => 'mouse',
            'oxen' => 'ox',
        ];

        $lower = strtolower($value);

        if (isset($irregular[$lower])) {
            return substr($value, 0, -strlen($lower)) . $irregular[$lower];
        }

        if (str_ends_with($lower, 'ies') && strlen($value) > 3) {
            return substr($value, 0, -3) . 'y';
        }

        if (str_ends_with($lower, 'ves')) {
            return substr($value, 0, -3) . 'f';
        }

        if (str_ends_with($lower, 'es') && (str_ends_with($lower, 'ses') || str_ends_with($lower, 'xes') || str_ends_with($lower, 'zes') || str_ends_with($lower, 'ches') || str_ends_with($lower, 'shes'))) {
            return substr($value, 0, -2);
        }

        if (str_ends_with($lower, 's') && !str_ends_with($lower, 'ss')) {
            return substr($value, 0, -1);
        }

        return $value;
    }
}
