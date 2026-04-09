<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static bool attempt(array $credentials)
 * @method static void login(\Zenith\Database\Model $user)
 * @method static void loginUsingId(int|string $id)
 * @method static void logout()
 * @method static bool check()
 * @method static bool guest()
 * @method static \Zenith\Database\Model|null user()
 * @method static int|string|null id()
 * @method static void logoutCurrentDevice()
 * @method static \Zenith\Auth\Auth guard(string|null $name = null)
 *
 * @see \Zenith\Auth\Auth
 */
class Auth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Auth\Auth::class;
    }
}
