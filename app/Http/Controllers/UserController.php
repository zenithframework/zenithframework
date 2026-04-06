<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class UserController
{
    public function index(Request $request): Response
    {
        return response('UserController@index');
    }

    public function show(Request $request, mixed ...$params): Response
    {
        return response('UserController@show');
    }

    public function store(Request $request): Response
    {
        return response('UserController@store', 201);
    }

    public function update(Request $request, mixed ...$params): Response
    {
        return response('UserController@update');
    }

    public function destroy(Request $request, mixed ...$params): Response
    {
        return response('', 204);
    }
}