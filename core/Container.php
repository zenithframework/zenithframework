<?php

declare(strict_types=1);

namespace Zenith;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $singletons = [];

    public function bind(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, Closure|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
        $this->singletons[$abstract] = true;
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];

            if ($concrete instanceof Closure) {
                $instance = $concrete($this);
            } else {
                $instance = $this->build($concrete);
            }

            if (isset($this->singletons[$abstract])) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        return $this->build($abstract);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    protected function build(string $concrete): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Cannot build [{$concrete}]: {$e->getMessage()}", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException("Cannot instantiate [{$concrete}]");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = array_map(
            fn(ReflectionParameter $parameter) => $this->resolveDependency($parameter),
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function resolveDependency(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new \RuntimeException("Cannot resolve parameter [{$parameter->getName()}]");
        }

        return $this->make($type->getName());
    }
}
