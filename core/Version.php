<?php

declare(strict_types=1);

namespace ZenithFramework;

class Version
{
    public const VERSION = '3.0.0';
    public const CODENAME = 'Enterprise';
    public const RELEASE_DATE = '2026-04-09';
    public const PHP_VERSION_REQUIRED = '8.5.0';

    public static function get(): string
    {
        return self::VERSION;
    }

    public static function getFull(): string
    {
        return sprintf(
            'Zenith Framework %s "%s" (PHP %s+)',
            self::VERSION,
            self::CODENAME,
            self::PHP_VERSION_REQUIRED
        );
    }

    public static function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'codename' => self::CODENAME,
            'release_date' => self::RELEASE_DATE,
            'php_required' => self::PHP_VERSION_REQUIRED,
            'php_current' => PHP_VERSION,
        ];
    }

    public static function checkCompatibility(): bool
    {
        return version_compare(PHP_VERSION, self::PHP_VERSION_REQUIRED, '>=');
    }

    public static function getBadge(): string
    {
        $length = strlen(self::getFull());
        $border = str_repeat('─', $length + 4);

        return sprintf(
            "┌%s┐\n│  %s  │\n└%s┘",
            $border,
            self::getFull(),
            $border
        );
    }
}
