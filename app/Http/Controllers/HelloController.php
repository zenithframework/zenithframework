<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class HelloController
{
    public function greet(Request $request, string $name): Response
    {
        return response("Hello, {$name}!");
    }
}