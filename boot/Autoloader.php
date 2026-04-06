<?php

declare(strict_types=1);

namespace Zen\Boot;

class Autoloader
{
    protected array $prefixes = [
        'Zen\\Boot\\' => __DIR__ . '/',
        'Zen\\' => __DIR__ . '/../core/',
        'Zen\\Database\\' => __DIR__ . '/../core/Database/',
        'Zen\\Cache\\' => __DIR__ . '/../core/Cache/',
        'Zen\\Validation\\' => __DIR__ . '/../core/Validation/',
        'Zen\\UI\\' => __DIR__ . '/../core/UI/',
        'Zen\\Session\\' => __DIR__ . '/../core/Session/',
        'Zen\\AI\\' => __DIR__ . '/../core/AI/',
        'Zen\\Action\\' => __DIR__ . '/../core/Action/',
        'Zen\\Log\\' => __DIR__ . '/../core/Log/',
        'Zen\\Auth\\' => __DIR__ . '/../core/Auth/',
        'App\\' => __DIR__ . '/../app/',
        'Tests\\' => __DIR__ . '/../tests/',
    ];

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $class): void
    {
        foreach ($this->prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
}
