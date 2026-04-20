<?php

declare(strict_types=1);

namespace Zenith\Container;

use Closure;
use Zenith\Container;

class ContextualBindingBuilder
{
    protected Container $container;
    protected string $concrete;

    public function __construct(Container $container, string $concrete)
    {
        $this->container = $container;
        $this->concrete = $concrete;
    }

    /**
     * Define the abstract to bind.
     *
     * @param string $abstract
     * @return $this
     */
    public function needs(string $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Give the implementation.
     *
     * @param Closure|string $implementation
     * @return void
     */
    public function give(Closure|string $implementation): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            $implementation
        );
    }

    /**
     * Give a tagged binding.
     *
     * @param string $tag
     * @return void
     */
    public function giveTagged(string $tag): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            fn($container) => $container->tagged($tag)
        );
    }

    /**
     * Give a specific instance.
     *
     * @param mixed $instance
     * @return void
     */
    public function giveInstance(mixed $instance): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            fn($container) => $instance
        );
    }

    /**
     * Give a configuration value.
     *
     * @param string $key
     * @return void
     */
    public function giveConfig(string $key): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            fn($container) => config($key)
        );
    }

    /**
     * Give an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return void
     */
    public function giveEnv(string $key, mixed $default = null): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            fn($container) => env($key, $default)
        );
    }
}
