<?php

declare(strict_types=1);

namespace Database\Seeders;

use Zen\Database\Seeder;

class Test extends Seeder
{
    public function run(): void
    {
        $table = Str::snake($name) . 's';
        
        $this->table($table)
            ->count(10)
            ->define('name', fn($id) => \Zen\Database\Factory::fake('name'))
            ->define('email', fn($id) => \Zen\Database\Factory::fake('email'))
            ->create();
    }
}