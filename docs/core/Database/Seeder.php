<?php

declare(strict_types=1);

namespace Zenith\Database;

class Seeder
{
    public function run(): void
    {
    }

    protected function call(string $seederClass): void
    {
        $seeder = new $seederClass();
        $seeder->run();
    }

    protected function table(string $table): Factory
    {
        return new Factory($table);
    }
}
