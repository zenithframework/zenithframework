<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class DebugLogCommand extends Command
{
    public function handle(Container $container, array $arguments): void
    {
        $logPath = dirname(__DIR__, 3) . '/storage/logs';
        
        if (!is_dir($logPath)) {
            $this->error("No logs directory found at: {$logPath}");
            return;
        }

        $logFile = $logPath . '/' . date('Y-m-d') . '.log';
        
        if (!file_exists($logFile)) {
            $this->warn("No log file found for today (" . date('Y-m-d') . ")");
            
            // Look for recent log files
            $logFiles = glob($logPath . '/*.log');
            if (empty($logFiles)) {
                $this->info("No log files found.");
                return;
            }
            
            $this->info("Recent log files:");
            foreach (array_slice($logFiles, -5) as $file) {
                $size = round(filesize($file) / 1024, 2);
                $modified = date('Y-m-d H:i:s', filemtime($file));
                $this->line("  {$file} ({$size} KB, {$modified})");
            }
            return;
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $totalLines = count($lines);

        $this->info("📝 Today's Log File: {$logFile}");
        $this->info("Total entries: {$totalLines}\n");

        // Show last 50 entries by default
        $displayLines = array_slice($lines, -50);
        
        foreach ($displayLines as $line) {
            // Parse log format: [YYYY-MM-DD HH:MM:SS] LEVEL: message
            if (preg_match('/\[(.*?)\] (\w+): (.+)/', $line, $matches)) {
                $timestamp = $matches[1];
                $level = strtoupper($matches[2]);
                $message = $matches[3];
                
                $color = match ($level) {
                    'ERROR' => 'red',
                    'WARNING' => 'yellow',
                    'DEBUG' => 'magenta',
                    default => 'white',
                };
                
                $this->line("  <fg={$color}>[{$timestamp}] {$level}</>: {$message}");
            } else {
                $this->line("  {$line}");
            }
        }

        if ($totalLines > 50) {
            $this->info("\nShowing last 50 of {$totalLines} entries. Use --lines=N to show more.");
        }
    }
}
