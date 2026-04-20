<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class SchedulerRunCommand extends Command
{
    protected string $name = 'scheduler:run';

    protected string $description = 'Run scheduled tasks';

    public function handle(Container $container, array $arguments): void
    {
        $scheduleFile = dirname(__DIR__, 3) . '/storage/schedule/schedule.json';

        if (!file_exists($scheduleFile)) {
            $this->warn('No schedule configuration found.');
            $this->line('Define scheduled tasks in app/Providers/ScheduleServiceProvider.php');
            return;
        }

        $this->info('Running scheduled tasks...');
        $this->line('');

        $schedule = json_decode(file_get_contents($scheduleFile), true) ?: [];
        $now = time();
        $executed = 0;

        foreach ($schedule as $task) {
            $command = $task['command'] ?? null;
            $expression = $task['expression'] ?? '* * * * *';
            $lastRun = $task['last_run'] ?? 0;

            if ($command === null) {
                continue;
            }

            if ($this->isDue($expression, $now) && ($now - $lastRun >= 60)) {
                $this->line("  Running: {$command}");

                $output = [];
                $returnCode = 0;

                exec($command . ' 2>&1', $output, $returnCode);

                if ($returnCode === 0) {
                    $this->info("    Completed successfully.");
                } else {
                    $this->error("    Failed with code: {$returnCode}");
                    $this->error("    Output: " . implode("\n    ", $output));
                }

                $task['last_run'] = $now;
                $executed++;
            }
        }

        if ($executed === 0) {
            $this->line('  No tasks due for execution.');
        }

        $this->line('');
        $this->info("Executed {$executed} task(s).");
    }

    protected function isDue(string $expression, int $timestamp): bool
    {
        $parts = explode(' ', $expression);

        if (count($parts) !== 5) {
            return false;
        }

        $date = getdate($timestamp);

        [$minuteExpr, $hourExpr, $dayExpr, $monthExpr, $weekdayExpr] = $parts;

        return $this->matchExpression($minuteExpr, $date['minutes'], 0, 59)
            && $this->matchExpression($hourExpr, $date['hours'], 0, 23)
            && $this->matchExpression($dayExpr, $date['mday'], 1, 31)
            && $this->matchExpression($monthExpr, $date['mon'], 1, 12)
            && $this->matchExpression($weekdayExpr, $date['wday'], 0, 6);
    }

    protected function matchExpression(string $expression, int $value, int $min, int $max): bool
    {
        if ($expression === '*') {
            return true;
        }

        if (str_contains($expression, ',')) {
            $values = array_map('intval', explode(',', $expression));
            return in_array($value, $values, true);
        }

        if (str_contains($expression, '/')) {
            [$base, $step] = explode('/', $expression);
            $step = (int) $step;

            if ($base === '*') {
                return $value % $step === 0;
            }

            $base = (int) $base;
            return $value >= $base && ($value - $base) % $step === 0;
        }

        if (str_contains($expression, '-')) {
            [$start, $end] = explode('-', $expression);
            return $value >= (int) $start && $value <= (int) $end;
        }

        return $value === (int) $expression;
    }
}
