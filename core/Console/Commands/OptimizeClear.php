<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class OptimizeClear extends Command
{
    protected array $dirs = [
        'storage/framework/views' => 'Compiled views',
        'storage/framework/cache' => 'Application cache',
        'storage/framework/sessions' => 'Session files',
        'storage/logs' => 'Log files',
    ];

    public function handle(Container $container, array $arguments): void
    {
        $cleared = 0;
        $failed = 0;
        
        foreach ($this->dirs as $dir => $label) {
            $path = dirname(__DIR__, 2) . '/' . $dir;
            
            if (!is_dir($path)) {
                continue;
            }
            
            $files = glob($path . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (@unlink($file)) {
                        $cleared++;
                    } else {
                        $failed++;
                    }
                }
            }
            
            $this->info("Cleared: {$label}");
        }
        
        $this->info("Total files cleared: {$cleared}");
        
        if ($failed > 0) {
            $this->warn("Failed to clear: {$failed} files");
        }
        
        $this->info("Optimize cache cleared successfully!");
    }
}