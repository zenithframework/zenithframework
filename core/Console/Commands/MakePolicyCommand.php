<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class MakePolicyCommand extends Command
{
    protected string $name = 'make:policy';
    protected string $description = 'Create a new authorization policy class';

    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? $this->ask('Enter policy name');
        $path = __DIR__ . '/../../../app/Policies/' . $name . 'Policy.php';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\\{$name};
use App\Models\User;

class {$name}Policy
{
    public function viewAny(User \$user): bool
    {
        return true;
    }

    public function view(User \$user, {$name} \$model): bool
    {
        return true;
    }

    public function create(User \$user): bool
    {
        return true;
    }

    public function update(User \$user, {$name} \$model): bool
    {
        return \$user->id === \$model->user_id;
    }

    public function delete(User \$user, {$name} \$model): bool
    {
        return \$user->id === \$model->user_id;
    }

    public function restore(User \$user, {$name} \$model): bool
    {
        return \$user->id === \$model->user_id;
    }

    public function forceDelete(User \$user, {$name} \$model): bool
    {
        return \$user->id === \$model->user_id;
    }
}
PHP;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        $this->info("✅ Policy created successfully: {$name}Policy");
        $this->line("   Path: {$path}");
        $this->line('');
    }
}
