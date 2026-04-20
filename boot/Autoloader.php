<?php

declare(strict_types=1);

namespace Zenith\Boot;

class Autoloader
{
    protected array $prefixes = [
        'Zenith\\Boot\\' => __DIR__ . '/',
        'Zenith\\' => __DIR__ . '/../core/',
        'Zenith\\Database\\' => __DIR__ . '/../core/Database/',
        'Zenith\\Cache\\' => __DIR__ . '/../core/Cache/',
        'Zenith\\Validation\\' => __DIR__ . '/../core/Validation/',
        'Zenith\\UI\\' => __DIR__ . '/../core/UI/',
        'Zenith\\Session\\' => __DIR__ . '/../core/Session/',
        'Zenith\\AI\\' => __DIR__ . '/../core/AI/',
        'Zenith\\Action\\' => __DIR__ . '/../core/Action/',
        'Zenith\\Log\\' => __DIR__ . '/../core/Log/',
        'Zenith\\Auth\\' => __DIR__ . '/../core/Auth/',
        'Zenith\\Event\\' => __DIR__ . '/../core/Event/',
        'Zenith\\Queue\\' => __DIR__ . '/../core/Queue/',
        'Zenith\\Mail\\' => __DIR__ . '/../core/Mail/',
        'Zenith\\Storage\\' => __DIR__ . '/../core/Storage/',
        'Zenith\\Http\\' => __DIR__ . '/../core/Http/',
        'Zenith\\Routing\\' => __DIR__ . '/../core/Routing/',
        'Zenith\\Security\\' => __DIR__ . '/../core/Security/',
        'Zenith\\Security\\RateLimit\\' => __DIR__ . '/../core/Security/RateLimit/',
        'Zenith\\Security\\AI\\' => __DIR__ . '/../core/Security/AI/',
        'Zenith\\Resilience\\' => __DIR__ . '/../core/Resilience/',
        'Zenith\\Server\\' => __DIR__ . '/../core/Server/',
        'Zenith\\Performance\\' => __DIR__ . '/../core/Performance/',
        'Zenith\\WebSocket\\' => __DIR__ . '/../core/WebSocket/',
        'Zenith\\Observability\\' => __DIR__ . '/../core/Observability/',
        'Zenith\\Cluster\\' => __DIR__ . '/../core/Cluster/',
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
