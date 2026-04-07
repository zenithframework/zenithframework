<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zen\Http\Request;
use Zen\Http\Response;

class ApiController
{
    public function status(Request $request): Response
    {
        return json(['status' => 'ok', 'version' => '1.0.0']);
    }

    public function dataStore(Request $request): Response
    {
        return json(['received' => $request->all()]);
    }
}