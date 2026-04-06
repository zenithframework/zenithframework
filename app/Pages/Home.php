<?php

declare(strict_types=1);

namespace App\Pages;

class Home
{
    public function render(): string
    {
        return view('pages.Home');
    }
}