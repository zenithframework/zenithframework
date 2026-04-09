<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Session\Session;

class SessionClearCommand extends Command
{
    protected string $name = 'session:clear';

    protected string $description = 'Clear all session data';

    public function handle(Container $container, array $arguments): void
    {
        $force = in_array('--force', $arguments);

        if (!$force) {
            $confirm = $this->confirm('This will clear all session data. Continue?');

            if (!$confirm) {
                $this->warn('Session clear cancelled.');
                return;
            }
        }

        $sessionPath = dirname(__DIR__, 3) . '/storage/framework/sessions';
        $cleared = 0;

        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/*');

            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        if (unlink($file)) {
                            $cleared++;
                        }
                    }
                }
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            Session::invalidate();
        }

        if ($cleared === 0) {
            $this->warn('No session data found to clear.');
        } else {
            $this->info("Cleared {$cleared} session file(s).");
        }
    }
}
