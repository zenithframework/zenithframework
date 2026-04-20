<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class EventListCommand extends Command
{
    protected string $name = 'event:list';

    protected string $description = 'List all registered events and listeners';

    public function handle(Container $container, array $arguments): void
    {
        $eventConfigFile = dirname(__DIR__, 3) . '/config/events.php';

        if (!file_exists($eventConfigFile)) {
            $this->warn('No events configuration found.');
            $this->line('Define events in config/events.php');
            return;
        }

        $events = require $eventConfigFile;

        if (!is_array($events) || empty($events)) {
            $this->warn('No events registered.');
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Events and Listeners');
        $this->line(str_repeat('-', 80));

        foreach ($events as $event => $listeners) {
            $this->line("  Event: {$event}");

            if (is_array($listeners)) {
                foreach ($listeners as $index => $listener) {
                    $prefix = $index === 0 ? '  Listeners:' : '            ';

                    if (is_array($listener)) {
                        $listener = $listener['listener'] ?? json_encode($listener);
                    }

                    $this->line("    {$prefix} => {$listener}");
                }
            } else {
                $this->line("    => {$listeners}");
            }

            $this->line('');
        }

        $this->line(str_repeat('-', 80));
        $this->info("Total: " . count($events) . " event(s)");
    }
}
