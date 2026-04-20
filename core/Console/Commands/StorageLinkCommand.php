<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class StorageLinkCommand extends Command
{
    protected string $name = 'storage:link';

    protected string $description = 'Create symbolic link for storage';

    public function handle(Container $container, array $arguments): void
    {
        $basePath = dirname(__DIR__, 3);
        $target = $basePath . '/storage/app/public';
        $link = $basePath . '/public/storage';

        if (PHP_OS_FAMILY === 'Windows') {
            $this->createWindowsLink($target, $link);
        } else {
            $this->createUnixLink($target, $link);
        }
    }

    protected function createUnixLink(string $target, string $link): void
    {
        if (is_link($link)) {
            $this->warn('Symbolic link already exists.');
            $this->line("  Link: {$link}");
            return;
        }

        if (file_exists($link)) {
            $this->error("A file or directory already exists at: {$link}");
            return;
        }

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
            $this->line("  Created storage directory: {$target}");
        }

        $result = symlink($target, $link);

        if (!$result) {
            $this->error('Failed to create symbolic link.');
            return;
        }

        $this->info('Symbolic link created successfully.');
        $this->line("  Target: {$target}");
        $this->line("  Link:   {$link}");
    }

    protected function createWindowsLink(string $target, string $link): void
    {
        if (is_link($link)) {
            $this->warn('Symbolic link already exists.');
            $this->line("  Link: {$link}");
            return;
        }

        if (file_exists($link)) {
            $this->error("A file or directory already exists at: {$link}");
            return;
        }

        if (!is_dir($target)) {
            mkdir($target, 0755, true);
            $this->line("  Created storage directory: {$target}");
        }

        $result = symlink($target, $link);

        if (!$result) {
            $this->warn('Failed to create symbolic link. You may need to run as Administrator.');
            $this->error('On Windows, enable Developer Mode or run: mklink /D "' . $link . '" "' . $target . '"');
            return;
        }

        $this->info('Symbolic link created successfully.');
        $this->line("  Target: {$target}");
        $this->line("  Link:   {$link}");
    }
}
