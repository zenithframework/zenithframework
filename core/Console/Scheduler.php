<?php

declare(strict_types=1);

namespace Zenith\Console;

/**
 * Task Scheduler - Cron-like scheduling system
 * 
 * Allows scheduling of commands and callbacks with fluent DSL
 */
class Scheduler
{
    protected array $events = [];
    protected string $outputPath;

    public function __construct()
    {
        $this->outputPath = storage_path('logs/schedule.log');
    }

    /**
     * Schedule a command
     */
    public function command(string $command, array $arguments = []): Event
    {
        $event = new ScheduledEvent($command, $arguments);
        $this->events[] = $event;
        return $event;
    }

    /**
     * Schedule a callback
     */
    public function call(callable $callback): Event
    {
        $event = new CallbackEvent($callback);
        $this->events[] = $event;
        return $event;
    }

    /**
     * Schedule an exec (system command)
     */
    public function exec(string $command): Event
    {
        $event = new ExecEvent($command);
        $this->events[] = $event;
        return $event;
    }

    /**
     * Get all scheduled events
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Run due events
     */
    public function runDue(?int $timestamp = null): void
    {
        $timestamp = $timestamp ?? time();
        $ran = 0;

        foreach ($this->events as $event) {
            if ($event->isDue($timestamp)) {
                $event->run();
                $ran++;
            }
        }

        if ($ran > 0) {
            $this->log("Ran {$ran} scheduled event(s)");
        }
    }

    /**
     * List all scheduled events
     */
    public function list(): array
    {
        $output = [];

        foreach ($this->events as $event) {
            $output[] = [
                'expression' => $event->getExpression(),
                'command' => $event->getCommand(),
                'description' => $event->getDescription(),
                'without_overlapping' => $event->preventsOverlapping(),
                'on_one_server' => $event->runsOnOneServer(),
            ];
        }

        return $output;
    }

    /**
     * Log scheduler activity
     */
    protected function log(string $message): void
    {
        $logDir = dirname($this->outputPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(
            $this->outputPath,
            date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }
}

/**
 * Base scheduled event
 */
abstract class Event
{
    protected string $expression = '* * * * *';
    protected ?string $description = null;
    protected bool $withoutOverlapping = false;
    protected bool $onOneServer = false;
    protected ?string $output = null;
    protected array $environments = [];

    /**
     * Set cron expression
     */
    public function cron(string $expression): static
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Every minute
     */
    public function everyMinute(): static
    {
        $this->expression = '* * * * *';
        return $this;
    }

    /**
     * Every five minutes
     */
    public function everyFiveMinutes(): static
    {
        $this->expression = '*/5 * * * *';
        return $this;
    }

    /**
     * Every ten minutes
     */
    public function everyTenMinutes(): static
    {
        $this->expression = '*/10 * * * *';
        return $this;
    }

    /**
     * Every fifteen minutes
     */
    public function everyFifteenMinutes(): static
    {
        $this->expression = '*/15 * * * *';
        return $this;
    }

    /**
     * Every thirty minutes
     */
    public function everyThirtyMinutes(): static
    {
        $this->expression = '*/30 * * * *';
        return $this;
    }

    /**
     * Hourly
     */
    public function hourly(): static
    {
        $this->expression = '0 * * * *';
        return $this;
    }

    /**
     * Hourly at specific minute
     */
    public function hourlyAt(int $minute): static
    {
        $this->expression = "{$minute} * * * *";
        return $this;
    }

    /**
     * Daily
     */
    public function daily(): static
    {
        $this->expression = '0 0 * * *';
        return $this;
    }

    /**
     * Daily at specific time
     */
    public function dailyAt(string $time): static
    {
        [$hour, $minute] = explode(':', $time);
        $this->expression = "{$minute} {$hour} * * *";
        return $this;
    }

    /**
     * Weekly
     */
    public function weekly(): static
    {
        $this->expression = '0 0 * * 0';
        return $this;
    }

    /**
     * Weekly on specific day
     */
    public function weeklyOn(int $dayOfWeek, string $time = '0:0'): static
    {
        [$hour, $minute] = explode(':', $time);
        $this->expression = "{$minute} {$hour} * * {$dayOfWeek}";
        return $this;
    }

    /**
     * Monthly
     */
    public function monthly(): static
    {
        $this->expression = '0 0 1 * *';
        return $this;
    }

    /**
     * Monthly on specific day
     */
    public function monthlyOn(int $dayOfMonth, string $time = '0:0'): static
    {
        [$hour, $minute] = explode(':', $time);
        $this->expression = "{$minute} {$hour} {$dayOfMonth} * *";
        return $this;
    }

    /**
     * Yearly
     */
    public function yearly(): static
    {
        $this->expression = '0 0 1 1 *';
        return $this;
    }

    /**
     * Weekdays only
     */
    public function weekdays(): static
    {
        $this->expression = '0 0 * * 1-5';
        return $this;
    }

    /**
     * Weekends only
     */
    public function weekends(): static
    {
        $this->expression = '0 0 * * 6,0';
        return $this;
    }

    /**
     * Mondays
     */
    public function mondays(): static
    {
        return $this->days(1);
    }

    /**
     * Tuesdays
     */
    public function tuesdays(): static
    {
        return $this->days(2);
    }

    /**
     * Wednesdays
     */
    public function wednesdays(): static
    {
        return $this->days(3);
    }

    /**
     * Thursdays
     */
    public function thursdays(): static
    {
        return $this->days(4);
    }

    /**
     * Fridays
     */
    public function fridays(): static
    {
        return $this->days(5);
    }

    /**
     * Saturdays
     */
    public function saturdays(): static
    {
        return $this->days(6);
    }

    /**
     * Sundays
     */
    public function sundays(): static
    {
        return $this->days(0);
    }

    /**
     * Specific days of week
     */
    public function days(int ...$days): static
    {
        $daysStr = implode(',', $days);
        $this->expression = "0 0 * * {$daysStr}";
        return $this;
    }

    /**
     * Between start and end time
     */
    public function between(string $startTime, string $endTime): static
    {
        // Implementation for time range checking
        return $this;
    }

    /**
     * When callback returns true
     */
    public function when(callable $callback): static
    {
        // Implementation for conditional scheduling
        return $this;
    }

    /**
     * Skip callback (opposite of when)
     */
    public function skip(callable $callback): static
    {
        // Implementation for conditional skipping
        return $this;
    }

    /**
     * Prevent overlapping runs
     */
    public function withoutOverlapping(): static
    {
        $this->withoutOverlapping = true;
        return $this;
    }

    /**
     * Run on one server only (for distributed systems)
     */
    public function onOneServer(): static
    {
        $this->onOneServer = true;
        return $this;
    }

    /**
     * Set output file
     */
    public function sendOutputTo(string $path): static
    {
        $this->output = $path;
        return $this;
    }

    /**
     * Append output to file
     */
    public function appendOutputTo(string $path): static
    {
        $this->output = $path;
        return $this;
    }

    /**
     * Email output to address
     */
    public function emailOutputTo(string $email): static
    {
        // Implementation for emailing output
        return $this;
    }

    /**
     * Set environments to run in
     */
    public function environments(string ...$envs): static
    {
        $this->environments = $envs;
        return $this;
    }

    /**
     * Set description
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Check if event is due
     */
    abstract public function isDue(int $timestamp): bool;

    /**
     * Run the event
     */
    abstract public function run(): void;

    /**
     * Get cron expression
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * Get command
     */
    abstract public function getCommand(): string;

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Check if prevents overlapping
     */
    public function preventsOverlapping(): bool
    {
        return $this->withoutOverlapping;
    }

    /**
     * Check if runs on one server
     */
    public function runsOnOneServer(): bool
    {
        return $this->onOneServer;
    }
}

/**
 * Scheduled command event
 */
class ScheduledEvent extends Event
{
    protected string $command;
    protected array $arguments;

    public function __construct(string $command, array $arguments = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    public function isDue(int $timestamp): bool
    {
        $date = getdate($timestamp);
        $minute = $date['minutes'];
        $hour = $date['hours'];
        $day = $date['mday'];
        $month = $date['mon'];
        $weekday = $date['wday'];

        $parts = explode(' ', $this->expression);

        if (count($parts) !== 5) {
            return false;
        }

        [$cronMinute, $cronHour, $cronDay, $cronMonth, $cronWeekday] = $parts;

        return $this->matchesField($minute, $cronMinute)
            && $this->matchesField($hour, $cronHour)
            && $this->matchesField($day, $cronDay)
            && $this->matchesField($month, $cronMonth)
            && $this->matchesField($weekday, $cronWeekday);
    }

    protected function matchesField(int $value, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        // Handle ranges: 1-5
        if (strpos($pattern, '-') !== false) {
            [$min, $max] = explode('-', $pattern);
            return $value >= (int) $min && $value <= (int) $max;
        }

        // Handle lists: 1,3,5
        if (strpos($pattern, ',') !== false) {
            $values = array_map('intval', explode(',', $pattern));
            return in_array($value, $values);
        }

        // Handle steps: */5
        if (strpos($pattern, '/') !== false) {
            [$base, $step] = explode('/', $pattern);
            if ($base === '*') {
                return $value % (int) $step === 0;
            }
        }

        return $value === (int) $pattern;
    }

    public function run(): void
    {
        $command = $this->command;
        $args = implode(' ', array_map('escapeshellarg', $this->arguments));
        
        exec("php zen {$command} {$args} > /dev/null 2>&1 &");
    }

    public function getCommand(): string
    {
        return $this->command . ' ' . implode(' ', $this->arguments);
    }
}

/**
 * Callback event
 */
class CallbackEvent extends Event
{
    protected $callback; // callable

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function isDue(int $timestamp): bool
    {
        // Same cron checking logic
        $date = getdate($timestamp);
        $parts = explode(' ', $this->expression);

        if (count($parts) !== 5) {
            return false;
        }

        [$cronMinute, $cronHour, $cronDay, $cronMonth, $cronWeekday] = $parts;

        return $this->matchesField($date['minutes'], $cronMinute)
            && $this->matchesField($date['hours'], $cronHour)
            && $this->matchesField($date['mday'], $cronDay)
            && $this->matchesField($date['mon'], $cronMonth)
            && $this->matchesField($date['wday'], $cronWeekday);
    }

    protected function matchesField(int $value, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (strpos($pattern, '-') !== false) {
            [$min, $max] = explode('-', $pattern);
            return $value >= (int) $min && $value <= (int) $max;
        }

        if (strpos($pattern, ',') !== false) {
            $values = array_map('intval', explode(',', $pattern));
            return in_array($value, $values);
        }

        if (strpos($pattern, '/') !== false) {
            [$base, $step] = explode('/', $pattern);
            if ($base === '*') {
                return $value % (int) $step === 0;
            }
        }

        return $value === (int) $pattern;
    }

    public function run(): void
    {
        call_user_func($this->callback);
    }

    public function getCommand(): string
    {
        return 'Closure';
    }
}

/**
 * Exec event (system command)
 */
class ExecEvent extends Event
{
    protected string $command;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function isDue(int $timestamp): bool
    {
        // Same cron checking logic as ScheduledEvent
        $date = getdate($timestamp);
        $parts = explode(' ', $this->expression);

        if (count($parts) !== 5) {
            return false;
        }

        [$cronMinute, $cronHour, $cronDay, $cronMonth, $cronWeekday] = $parts;

        return $this->matchesField($date['minutes'], $cronMinute)
            && $this->matchesField($date['hours'], $cronHour)
            && $this->matchesField($date['mday'], $cronDay)
            && $this->matchesField($date['mon'], $cronMonth)
            && $this->matchesField($date['wday'], $cronWeekday);
    }

    protected function matchesField(int $value, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (strpos($pattern, '-') !== false) {
            [$min, $max] = explode('-', $pattern);
            return $value >= (int) $min && $value <= (int) $max;
        }

        if (strpos($pattern, ',') !== false) {
            $values = array_map('intval', explode(',', $pattern));
            return in_array($value, $values);
        }

        if (strpos($pattern, '/') !== false) {
            [$base, $step] = explode('/', $pattern);
            if ($base === '*') {
                return $value % (int) $step === 0;
            }
        }

        return $value === (int) $pattern;
    }

    public function run(): void
    {
        exec($this->command);
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
