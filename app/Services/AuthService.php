<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Zen\Auth\Auth;
use Zen\Validation\Validator;

class AuthService
{
    public function login(array $credentials): ?User
    {
        if (Auth::attempt($credentials)) {
            return Auth::user();
        }
        
        return null;
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function register(array $data): User
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            throw new \RuntimeException(json_encode($validator->errors()));
        }

        $user = User::create($data);
        Auth::login($user);

        return $user;
    }

    public function getUser(): ?User
    {
        return Auth::user();
    }

    public function isAuthenticated(): bool
    {
        return Auth::check();
    }

    public function validateCredentials(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }
}
