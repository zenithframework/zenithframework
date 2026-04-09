<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Support\Str;

class MakeRequest extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;

        if ($name === null) {
            $this->error('Request name is required.');
            $this->info('Usage: php zen make:request <name>');
            return;
        }

        $className = Str::studly($name) . 'Request';
        $filename = $className . '.php';
        $path = __DIR__ . '/../../../app/Http/Requests/' . $filename;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (file_exists($path)) {
            $this->error("Request [{$name}] already exists.");
            return;
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'name' => 'required|string|min:2',
            // 'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            // 'name.required' => 'The name field is required.',
        ];
    }
}
PHP;

        file_put_contents($path, $content);
        $this->info("Request [{$className}] created successfully.");
    }
}
