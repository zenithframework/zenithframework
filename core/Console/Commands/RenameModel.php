<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;
use Zen\Support\Str;

class RenameModel extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $name = $arguments[0] ?? null;
        $toIndex = array_search('--to=', $arguments);
        $newName = $toIndex !== false ? $arguments[$toIndex + 1] : null;

        if ($name === null || $newName === null) {
            $this->error('Model name and --to are required.');
            $this->info('Usage: php zen rename:model <name> --to=<NewName>');
            return;
        }

        $oldClassName = Str::studly($name);
        $newClassName = Str::studly($newName);
        
        $oldPath = __DIR__ . '/../../../app/Models/' . $oldClassName . '.php';
        $newPath = __DIR__ . '/../../../app/Models/' . $newClassName . '.php';

        if (!file_exists($oldPath)) {
            $this->error("Model [{$oldClassName}] not found.");
            return;
        }

        if (file_exists($newPath)) {
            $this->error("Model [{$newClassName}] already exists.");
            return;
        }

        $content = file_get_contents($oldPath);
        $content = str_replace("class {$oldClassName}", "class {$newClassName}", $content);
        
        file_put_contents($newPath, $content);
        unlink($oldPath);
        
        $this->info("Model [{$oldClassName}] renamed to [{$newClassName}] successfully.");
    }
}
