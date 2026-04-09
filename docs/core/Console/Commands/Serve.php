<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class Serve extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $host = '127.0.0.1';
        $port = 8000;

        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--host=')) {
                $host = substr($arg, 8);
            }
            if (str_starts_with($arg, '--port=')) {
                $port = (int) substr($arg, 8);
            }
        }

        $this->info("🚀 Starting Zen Framework development server...");
        $this->info("http://{$host}:{$port}");
        $this->line("Press Ctrl+C to stop the server\n");

        $publicDir = dirname(__DIR__, 3) . '/public';
        
        if (!is_dir($publicDir)) {
            $this->error("Public directory not found: {$publicDir}");
            return;
        }
        
        $command = sprintf('php -S %s:%d -t %s', $host, $port, $publicDir);
        
        system($command);
    }
}
