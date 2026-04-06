<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;
use Zen\Auth\Auth;
use Zen\Validation\Validator;
use App\Models\User;

class AuthController
{
    public function showLogin(Request $request): Response
    {
        return response('<h1>Login Page</h1><form method="POST" action="/auth/login">' . csrf_field() . '<input type="email" name="email"><input type="password" name="password"><button type="submit">Login</button></form>');
    }

    public function login(Request $request): Response
    {
        $credentials = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (Auth::attempt($credentials)) {
            return redirect('/dashboard');
        }

        return response('Invalid credentials', 401);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return redirect('/auth/login');
    }

    public function showRegister(Request $request): Response
    {
        return response('<h1>Register Page</h1><form method="POST" action="/auth/register">' . csrf_field() . '<input type="text" name="name"><input type="email" name="email"><input type="password" name="password"><button type="submit">Register</button></form>');
    }

    public function register(Request $request): Response
    {
        $data = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|min:2',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create($data);
        Auth::login($user);

        return redirect('/dashboard');
    }
}
