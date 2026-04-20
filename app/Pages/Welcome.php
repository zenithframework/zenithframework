<?php

declare(strict_types=1);

namespace App\Pages;

class Welcome
{
    public function render(): string
    {
        return view('pages.welcome');
    }
}