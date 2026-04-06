<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class MakeModel extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Model name is required.');
            $this->info('Usage: php zen make:model <name>');
            return;
        }

        $path = __DIR__ . '/../../../app/Models/' . $name . '.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Model [{$name}] already exists.");
            return;
        }

        $table = Str::plural(Str::snake($name));

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Models;

class {$name}
{
    protected string \$table = '{$table}';
    protected array \$fillable = [];
    protected array \$hidden = [];

    public static function find(int \$id): ?static
    {
        return null;
    }

    public static function all(): array
    {
        return [];
    }

    public static function where(string \$column, mixed \$value): array
    {
        return [];
    }

    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Model [{$name}] created successfully.");
    }
}
