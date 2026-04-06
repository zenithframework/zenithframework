<?php

declare(strict_types=1);

namespace Zen\Action;

class ActionHandler
{
    protected array $actions = [];

    public function register(array $actions): void
    {
        $this->actions = array_merge($this->actions, $actions);
    }

    public function handle(string $action, array $params = []): string
    {
        if (!isset($this->actions[$action])) {
            throw new \RuntimeException("Action [{$action}] not registered");
        }

        $class = $this->actions[$action];

        if (!class_exists($class)) {
            throw new \RuntimeException("Action class [{$class}] not found");
        }

        $instance = new $class();
        $method = 'handle';

        if (!method_exists($instance, $method)) {
            throw new \RuntimeException("Action [{$action}] must have a handle method");
        }

        $result = $instance->{$method}(...$params);

        return $this->formatResponse($result);
    }

    protected function formatResponse(mixed $result): string
    {
        if ($result === null) {
            return '';
        }

        if (is_string($result)) {
            return $result;
        }

        if (is_array($result)) {
            return json_encode($result, JSON_THROW_ON_ERROR);
        }

        return (string) $result;
    }

    public function has(string $action): bool
    {
        return isset($this->actions[$action]);
    }

    public function getRegisteredActions(): array
    {
        return $this->actions;
    }
}

class Action
{
    public function handle(mixed ...$args): mixed
    {
        return null;
    }
}
