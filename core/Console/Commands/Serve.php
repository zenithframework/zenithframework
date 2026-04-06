<?php

declare(strict_types=1);

namespace Zen\Console\Commands;

use Zen\Container;

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

        $command = sprintf('php -S %s:%d -t %s/public', $host, $port, dirname(__DIR__, 2));
        
        system($command);
    }
}
