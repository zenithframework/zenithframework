<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;

class EventTestCommand extends Command
{
    protected string $name = 'event:test';

    protected string $description = 'Test dispatch an event';

    public function handle(Container $container, array $arguments): void
    {
        $eventClass = $arguments[0] ?? null;

        if ($eventClass === null) {
            $eventClass = $this->ask('Enter the event class name (e.g., App\Events\UserRegistered)');
        }

        if (empty($eventClass)) {
            $this->error('Event class name is required.');
            return;
        }

        if (!class_exists($eventClass)) {
            $this->error("Event class [{$eventClass}] not found.");
            return;
        }

        $this->info("Dispatching event: {$eventClass}");

        try {
            $event = new $eventClass();

            $eventConfigFile = dirname(__DIR__, 3) . '/config/events.php';

            if (file_exists($eventConfigFile)) {
                $events = require $eventConfigFile;

                if (isset($events[$eventClass])) {
                    $listeners = $events[$eventClass];

                    if (is_array($listeners)) {
                        foreach ($listeners as $listener) {
                            $listenerClass = is_array($listener) ? ($listener['listener'] ?? null) : $listener;

                            if ($listenerClass !== null && class_exists($listenerClass)) {
                                $listenerInstance = new $listenerClass();

                                if (method_exists($listenerInstance, 'handle')) {
                                    $listenerInstance->handle($event);
                                    $this->info("  Listener called: {$listenerClass}");
                                } else {
                                    $this->warn("  Listener [{$listenerClass}] does not have a handle() method.");
                                }
                            } else {
                                $this->warn("  Listener class [{$listenerClass}] not found.");
                            }
                        }
                    }
                } else {
                    $this->warn("No listeners registered for event: {$eventClass}");
                }
            } else {
                $this->warn('No events configuration found.');
            }

            $this->info("Event dispatched successfully.");
        } catch (\Throwable $e) {
            $this->error("Failed to dispatch event: " . $e->getMessage());
        }
    }
}
