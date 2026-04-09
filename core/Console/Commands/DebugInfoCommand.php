<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class DebugInfoCommand extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $this->info("⚡ Zen Framework Debugger Information\n");

        $this->info("Environment:");
        $this->line("  APP_NAME:     " . config('app.name', 'Zen'));
        $this->line("  APP_ENV:      " . config('app.env', 'production'));
        $this->line("  APP_DEBUG:    " . (config('app.debug', false) ? 'true' : 'false'));
        $this->line("  PHP Version:  " . PHP_VERSION . "\n");

        $this->info("Debugger Status:");
        $isEnabled = config('app.debug', false) && config('app.env', 'production') !== 'production';
        $this->line("  Status:       " . ($isEnabled ? '✓ Enabled' : '✗ Disabled'));
        
        if (!$isEnabled) {
            $this->warn("\n  To enable the debugger:");
            $this->line("  1. Set APP_DEBUG=true in your .env file");
            $this->line("  2. Set APP_ENV=development in your .env file");
        } else {
            $this->line("\n  Features:");
            $this->line("  ✓ Debug Toolbar");
            $this->line("  ✓ Query Profiler");
            $this->line("  ✓ Log Viewer");
            $this->line("  ✓ Session Inspector");
            $this->line("  ✓ Cache Tracker");
            $this->line("  ✓ Request/Response Analyzer");
            $this->line("  ✓ Markdown Export");
        }

        $this->line("\n");

        $this->info("Helper Functions:");
        $this->line("  dump()        - Dump variables without stopping execution");
        $this->line("  dd()          - Dump variables and stop execution");
        $this->line("  debug_log()   - Log messages to the debug panel\n");

        $this->info("Usage:");
        $this->line("  // In your code:");
        $this->line("  dump(\$variable);");
        $this->line("  dump(\$var1, \$var2, \$var3);");
        $this->line("  debug_log('User logged in', 'info', ['user_id' => 123]);\n");

        $this->info("CLI Commands:");
        $this->line("  php zen debug:info     - Show debugger information");
        $this->line("  php zen debug:log      - View recent log entries");
        $this->line("  php zen debug:clear    - Clear debug cache\n");
    }
}
