<?php

declare(strict_types=1);

namespace Database\Seeders;

use Zenith\Database\Seeder;
use Zenith\Support\Str;

class Test extends Seeder
{
    public function run(): void
    {
        $name = $this->name ?? 'Test';
        $table = Str::snake($name) . 's';
        
        $this->table($table)
            ->count(10)
            ->define('name', fn($id) => \Zenith\Database\Factory::fake('name'))
            ->define('email', fn($id) => \Zenith\Database\Factory::fake('email'))
            ->create();
    }
}
