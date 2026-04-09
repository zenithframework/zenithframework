<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeSeeder extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Seeder name is required.');
            $this->info('Usage: php zen make:seeder <name>');
            return;
        }

        $className = Str::studly($name);
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../database/seeders/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Seeder [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Zenith\Database\Seeder;

class {$className} extends Seeder
{
    public function run(): void
    {
        \$table = Str::snake(\$name) . 's';
        
        \$this->table(\$table)
            ->count(10)
            ->define('name', fn(\$id) => \Zenith\Database\Factory::fake('name'))
            ->define('email', fn(\$id) => \Zenith\Database\Factory::fake('email'))
            ->create();
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Seeder [{$className}] created successfully.");
    }
}
