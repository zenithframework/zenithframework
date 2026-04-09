<?php

declare(strict_types=1);

namespace Zenith\Auth;

use App\Models\User;
use Zenith\Database\Model;

class Auth
{
    protected static ?Model $user = null;
    protected static string $guard = 'web';

    public static function user(): ?Model
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::$user?->id ?? null;
    }

    public static function check(): bool
    {
        return self::$user !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function login(Model $user): void
    {
        self::$user = $user;
        \Zenith\Session\Session::put('auth_' . self::$guard, $user->id);
    }

    public static function loginUsingId(int $id): bool
    {
        $user = User::find($id);
        
        if ($user === null) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function logout(): void
    {
        self::$user = null;
        \Zenith\Session\Session::forget('auth_' . self::$guard);
    }

    public static function attempt(array $credentials): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if ($email === null || $password === null) {
            return false;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        if (!isset($user->password) || !password_verify($password, $user->password)) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function once(array $credentials): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if ($email === null || $password === null) {
            return false;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return false;
        }

        if (!isset($user->password) || !password_verify($password, $user->password)) {
            return false;
        }

        self::$user = $user;
        return true;
    }

    public static function guard(string $guard): void
    {
        self::$guard = $guard;
    }

    public static function getGuard(): string
    {
        return self::$guard;
    }

    public static function loadFromSession(): void
    {
        $id = \Zenith\Session\Session::get('auth_' . self::$guard);

        if ($id !== null) {
            self::$user = User::find((int) $id);
        }
    }
}
