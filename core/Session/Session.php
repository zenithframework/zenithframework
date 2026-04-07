<?php

declare(strict_types=1);

namespace Zen\Session;

class Session
{
    protected static bool $started = false;
    protected static string $name = 'ZEN_SESSION';
    protected static string $path = '/';
    protected static ?string $domain = null;
    protected static bool $secure = false;
    protected static bool $httpOnly = true;
    protected static ?string $sameSite = 'Lax';

    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::$name);
            session_start();
        }

        self::$started = true;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function set(string $key, mixed $value): self
    {
        self::put($key, $value);
        return new self();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();

        if ($value === null) {
            $flashKey = '_flash.' . $key;
            $value = $_SESSION[$flashKey] ?? null;
            unset($_SESSION[$flashKey]);
            return $value;
        }

        $_SESSION['_flash.' . $key] = $value;
        return null;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function invalidate(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
        self::$started = false;
    }

    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    public static function token(): string
    {
        self::start();

        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }

    public static function setName(string $name): void
    {
        self::$name = $name;
    }

    public static function getName(): string
    {
        return self::$name;
    }

    public static function setConfig(array $config): void
    {
        if (isset($config['name'])) {
            self::$name = $config['name'];
        }

        if (isset($config['path'])) {
            self::$path = $config['path'];
        }

        if (isset($config['domain'])) {
            self::$domain = $config['domain'];
        }

        if (isset($config['secure'])) {
            self::$secure = $config['secure'];
        }

        if (isset($config['http_only'])) {
            self::$httpOnly = $config['http_only'];
        }

        if (isset($config['same_site'])) {
            self::$sameSite = $config['same_site'];
        }
    }

    public static function isStarted(): bool
    {
        return self::$started;
    }
}
