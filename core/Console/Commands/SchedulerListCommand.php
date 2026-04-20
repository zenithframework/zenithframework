<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class SchedulerListCommand extends Command
{
    protected string $name = 'scheduler:list';

    protected string $description = 'List all scheduled tasks';

    public function handle(Container $container, array $arguments): void
    {
        $scheduleFile = dirname(__DIR__, 3) . '/storage/schedule/schedule.json';

        if (!file_exists($scheduleFile)) {
            $this->warn('No schedule configuration found.');
            $this->line('Define scheduled tasks in app/Providers/ScheduleServiceProvider.php');
            return;
        }

        $schedule = json_decode(file_get_contents($scheduleFile), true) ?: [];

        if (empty($schedule)) {
            $this->warn('No scheduled tasks found.');
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Scheduled Tasks');
        $this->line(str_repeat('-', 80));

        foreach ($schedule as $index => $task) {
            $command = $task['command'] ?? 'N/A';
            $expression = $task['expression'] ?? '* * * * *';
            $description = $task['description'] ?? '';
            $lastRun = $task['last_run'] ?? null;

            $this->line(sprintf("  %d. Command:     %s", $index + 1, $command));
            $this->line(sprintf("     Schedule:    %s", $expression));
            $this->line(sprintf("     Description: %s", $description ?: '(none)'));

            if ($lastRun !== null) {
                $lastRunDate = date('Y-m-d H:i:s', $lastRun);
                $this->line(sprintf("     Last Run:    %s", $lastRunDate));
            } else {
                $this->line("     Last Run:    Never");
            }

            $this->line(str_repeat('-', 80));
        }

        $this->info("Total: " . count($schedule) . " scheduled task(s)");
    }
}
