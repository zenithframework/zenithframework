<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeMigration extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Migration name is required.');
            $this->info('Usage: php zen make:migration <name>');
            return;
        }

        $timestamp = date('Y_m_d_His');
        $filename = $timestamp . '_' . Str::snake($name) . '.php';
        $path = __DIR__ . '/../../../database/migrations/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Migration [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

return new class {
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
PHP;

        file_put_contents($path, $content);
        $this->info("Migration [{$filename}] created successfully.");
    }
}
