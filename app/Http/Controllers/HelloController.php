<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;

class HelloController
{
    public function greet(Request $request, string $name): Response
    {
        return response("Hello, {$name}!");
    }
}