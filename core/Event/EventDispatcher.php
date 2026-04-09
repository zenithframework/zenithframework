<?php

declare(strict_types=1);

namespace Zenith\Event;

use Zenith\Container;

class EventDispatcher
{
    protected array $listeners = [];
    protected array $wildcardListeners = [];

    public function listen(string $event, callable $listener, int $priority = 0): void
    {
        if (str_contains($event, '*')) {
            $this->wildcardListeners[$event][] = ['callback' => $listener, 'priority' => $priority];
            usort($this->wildcardListeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);
        } else {
            $this->listeners[$event][] = ['callback' => $listener, 'priority' => $priority];
            usort($this->listeners[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);
        }
    }

    public function dispatch(string $event, array $payload = []): Event
    {
        if ($event instanceof Event) {
            $eventObj = $event;
        } else {
            $eventObj = new Event($event, $payload);
        }

        $eventName = $eventObj->name;
        $listeners = $this->listeners[$eventName] ?? [];

        foreach ($this->wildcardListeners as $pattern => $wildcards) {
            if ($this->matchesPattern($eventName, $pattern)) {
                $listeners = array_merge($listeners, $wildcards);
            }
        }

        usort($listeners, fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($listeners as $listener) {
            if ($eventObj->propagationStopped) {
                break;
            }

            $callback = $listener['callback'];
            if (is_array($callback) && is_string($callback[0])) {
                $callback[0] = $this->resolveClass($callback[0]);
            }

            $result = $callback($eventObj);

            if ($result instanceof Event) {
                $eventObj = $result;
            }
        }

        return $eventObj;
    }

    public function dispatchAsync(string $event, array $payload = []): void
    {
    }

    public function subscribe(object $subscriber): void
    {
        $reflection = new \ReflectionClass($subscriber);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), 'on')) {
                $eventName = strtolower(substr($method->getName(), 2));
                $this->listen($eventName, [$subscriber, $method->getName()]);
            }
        }
    }

    public function hasListeners(string $event): bool
    {
        return !empty($this->listeners[$event]) || $this->hasWildcardListeners($event);
    }

    protected function hasWildcardListeners(string $event): bool
    {
        foreach ($this->wildcardListeners as $pattern => $listeners) {
            if ($this->matchesPattern($event, $pattern)) {
                return true;
            }
        }
        return false;
    }

    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    public function forgetAll(): void
    {
        $this->listeners = [];
        $this->wildcardListeners = [];
    }

    protected function matchesPattern(string $event, string $pattern): bool
    {
        $regex = str_replace(['/', '*'], ['\\/', '.*'], $pattern);
        return (bool) preg_match("/^{$regex}$/i", $event);
    }

    protected function resolveClass(string $class): object
    {
        if (class_exists($class)) {
            return app($class);
        }
        throw new \RuntimeException("Class [{$class}] does not exist");
    }
}