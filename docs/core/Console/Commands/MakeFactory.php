<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeFactory extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Factory name is required.');
            $this->info('Usage: php zen make:factory <name>');
            return;
        }

        $className = Str::studly($name);
        $filename = $className . 'Factory.php';
        $path = __DIR__ . '/../../../database/factories/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Factory [{$name}] already exists.");
            return;
        }

        $tableName = Str::snake($name) . 's';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Database\Factories;

use Zenith\Database\Factory;

class {$className}Factory extends Factory
{
    public function __construct()
    {
        parent::__construct('{$tableName}');
    }

    public function definition(int \$id): array
    {
        return [
            'name' => Factory::fake('name'),
            'email' => Factory::fake('email'),
        ];
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Factory [{$className}Factory] created successfully.");
    }
}
