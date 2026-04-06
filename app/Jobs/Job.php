<?php

declare(strict_types=1);

namespace App\Jobs;

abstract class Job
{
    public function handle(): void
    {
    }

    public function failed(\Throwable $exception): void
    {
    }
}
