<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Session\Session;

class SessionStatsCommand extends Command
{
    protected string $name = 'session:stats';

    protected string $description = 'Show session statistics';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $sessionPath = dirname(__DIR__, 3) . '/storage/framework/sessions';
        $sessionName = Session::getName();
        $sessionStatus = session_status();

        $stats = [
            'session_name' => $sessionName,
            'status' => match ($sessionStatus) {
                PHP_SESSION_DISABLED => 'disabled',
                PHP_SESSION_NONE => 'inactive',
                PHP_SESSION_ACTIVE => 'active',
                default => 'unknown',
            },
            'storage_path' => $sessionPath,
            'total_files' => 0,
            'total_size' => 0,
            'oldest' => null,
            'newest' => null,
        ];

        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/*') ?: [];
            $totalFiles = count($files);
            $totalSize = 0;
            $oldestTime = null;
            $newestTime = null;

            foreach ($files as $file) {
                if (is_file($file)) {
                    $totalSize += filesize($file);
                    $mtime = filemtime($file);

                    if ($oldestTime === null || $mtime < $oldestTime) {
                        $oldestTime = $mtime;
                    }

                    if ($newestTime === null || $mtime > $newestTime) {
                        $newestTime = $mtime;
                    }
                }
            }

            $stats['total_files'] = $totalFiles;
            $stats['total_size'] = $totalSize;
            $stats['oldest'] = $oldestTime !== null ? date('Y-m-d H:i:s', $oldestTime) : null;
            $stats['newest'] = $newestTime !== null ? date('Y-m-d H:i:s', $newestTime) : null;
        }

        if ($json) {
            echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 60));
        $this->info('Session Statistics');
        $this->line(str_repeat('-', 60));
        $this->line("  Session Name:   {$stats['session_name']}");
        $this->line("  Status:         {$stats['status']}");
        $this->line("  Storage Path:   {$stats['storage_path']}");
        $this->line("  Directory Exists: " . (is_dir($sessionPath) ? 'Yes' : 'No'));
        $this->line("  Total Files:    {$stats['total_files']}");
        $this->line("  Total Size:     " . $this->formatBytes($stats['total_size']));
        $this->line("  Oldest Session: " . ($stats['oldest'] ?? 'N/A'));
        $this->line("  Newest Session: " . ($stats['newest'] ?? 'N/A'));
        $this->line(str_repeat('-', 60));
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
