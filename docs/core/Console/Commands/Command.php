<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

abstract class Command
{
    protected Container $container;
    protected array $arguments = [];

    abstract public function handle(Container $container, array $arguments): void;

    protected function info(string $message): void
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    protected function error(string $message): void
    {
        echo "\033[31m{$message}\033[0m\n";
    }

    protected function warn(string $message): void
    {
        echo "\033[33m{$message}\033[0m\n";
    }

    protected function line(string $message = ''): void
    {
        echo "{$message}\n";
    }

    protected function confirm(string $question): bool
    {
        echo "{$question} (yes/no) ";
        $answer = fgets(STDIN);
        if ($answer === false) {
            return false;
        }
        $answer = trim($answer);
        return strtolower($answer) === 'yes' || strtolower($answer) === 'y';
    }

    protected function ask(string $question, ?string $default = null): string
    {
        $suffix = $default !== null ? " [{$default}]" : '';
        echo "{$question}{$suffix}: ";
        $answer = fgets(STDIN);
        if ($answer === false) {
            return $default ?? '';
        }
        $answer = trim($answer);
        return $answer !== '' ? $answer : ($default ?? '');
    }
}
