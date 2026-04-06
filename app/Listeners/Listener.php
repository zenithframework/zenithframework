<?php

declare(strict_types=1);

namespace App\Listeners;

abstract class Listener
{
    public function handle(\App\Events\Event $event): void
    {
    }
}
