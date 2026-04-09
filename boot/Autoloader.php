<?php

declare(strict_types=1);

namespace ZenithFramework\Boot;

class Autoloader
{
    protected array $prefixes = [
        'ZenithFramework\\Boot\\' => __DIR__ . '/',
        'ZenithFramework\\' => __DIR__ . '/../core/',
        'ZenithFramework\\Database\\' => __DIR__ . '/../core/Database/',
        'ZenithFramework\\Cache\\' => __DIR__ . '/../core/Cache/',
        'ZenithFramework\\Validation\\' => __DIR__ . '/../core/Validation/',
        'ZenithFramework\\UI\\' => __DIR__ . '/../core/UI/',
        'ZenithFramework\\Session\\' => __DIR__ . '/../core/Session/',
        'ZenithFramework\\AI\\' => __DIR__ . '/../core/AI/',
        'ZenithFramework\\Action\\' => __DIR__ . '/../core/Action/',
        'ZenithFramework\\Log\\' => __DIR__ . '/../core/Log/',
        'ZenithFramework\\Auth\\' => __DIR__ . '/../core/Auth/',
        'ZenithFramework\\Event\\' => __DIR__ . '/../core/Event/',
        'ZenithFramework\\Queue\\' => __DIR__ . '/../core/Queue/',
        'ZenithFramework\\Mail\\' => __DIR__ . '/../core/Mail/',
        'ZenithFramework\\Storage\\' => __DIR__ . '/../core/Storage/',
        'ZenithFramework\\Http\\' => __DIR__ . '/../core/Http/',
        'ZenithFramework\\Routing\\' => __DIR__ . '/../core/Routing/',
        'ZenithFramework\\Security\\' => __DIR__ . '/../core/Security/',
        'ZenithFramework\\Security\\RateLimit\\' => __DIR__ . '/../core/Security/RateLimit/',
        'ZenithFramework\\Security\\AI\\' => __DIR__ . '/../core/Security/AI/',
        'ZenithFramework\\Resilience\\' => __DIR__ . '/../core/Resilience/',
        'ZenithFramework\\Server\\' => __DIR__ . '/../core/Server/',
        'ZenithFramework\\Performance\\' => __DIR__ . '/../core/Performance/',
        'ZenithFramework\\WebSocket\\' => __DIR__ . '/../core/WebSocket/',
        'ZenithFramework\\Observability\\' => __DIR__ . '/../core/Observability/',
        'ZenithFramework\\Cluster\\' => __DIR__ . '/../core/Cluster/',
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
