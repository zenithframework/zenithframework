<?php

declare(strict_types=1);

namespace Database\Seeders;

use Zen\Database\Seeder;
use Zen\Support\Str;

class Test extends Seeder
{
    public function run(): void
    {
        $name = $this->name ?? 'Test';
        $table = Str::snake($name) . 's';
        
        $this->table($table)
            ->count(10)
            ->define('name', fn($id) => \Zen\Database\Factory::fake('name'))
            ->define('email', fn($id) => \Zen\Database\Factory::fake('email'))
            ->create();
    }
}
