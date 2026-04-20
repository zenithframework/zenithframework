<?php

declare(strict_types=1);

namespace Zenith\Auth\Guards;

use Closure;
use App\Models\User;

/**
 * Gate - Authorization manager
 * 
 * Defines and checks authorization abilities
 */
class Gate
{
    protected array $abilities = [];
    protected array $policies = [];
    protected array $beforeCallbacks = [];
    protected array $afterCallbacks = [];

    /**
     * Define a new ability
     */
    public function define(string $ability, callable|Closure $callback): void
    {
        $this->abilities[$ability] = $callback;
    }

    /**
     * Define abilities using array
     */
    public function abilities(array $abilities): void
    {
        foreach ($abilities as $ability => $callback) {
            $this->define($ability, $callback);
        }
    }

    /**
     * Register a policy class
     */
    public function policy(string $modelClass, string $policyClass): void
    {
        $this->policies[$modelClass] = $policyClass;
    }

    /**
     * Register multiple policies
     */
    public function policies(array $policies): void
    {
        foreach ($policies as $modelClass => $policyClass) {
            $this->policy($modelClass, $policyClass);
        }
    }

    /**
     * Register a "before" callback that runs before ability check
     */
    public function before(callable|Closure $callback): void
    {
        $this->beforeCallbacks[] = $callback;
    }

    /**
     * Register an "after" callback that runs after ability check
     */
    public function after(callable|Closure $callback): void
    {
        $this->afterCallbacks[] = $callback;
    }

    /**
     * Determine if ability is allowed
     */
    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->check($ability, ...$arguments);
    }

    /**
     * Determine if ability is denied
     */
    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->check($ability, ...$arguments);
    }

    /**
     * Check if ability is allowed
     */
    public function check(string $ability, mixed ...$arguments): bool
    {
        $user = auth()->user();

        // Run before callbacks
        foreach ($this->beforeCallbacks as $callback) {
            $result = call_user_func($callback, $user, $ability, ...$arguments);
            
            if ($result !== null) {
                return (bool) $result;
            }
        }

        // Check policies
        if (!empty($arguments)) {
            $model = $arguments[0];
            $policy = $this->getPolicyFor($model);
            
            if ($policy !== null && method_exists($policy, $ability)) {
                $policyInstance = app($policy);
                $result = call_user_func([$policyInstance, $ability], $user, ...$arguments);
                
                return $this->runAfterCallbacks($user, $ability, $result, $arguments);
            }
        }

        // Check abilities
        if (isset($this->abilities[$ability])) {
            $callback = $this->abilities[$ability];
            $result = call_user_func($callback, $user, ...$arguments);
            
            return $this->runAfterCallbacks($user, $ability, $result, $arguments);
        }

        return false;
    }

    /**
     * Run after callbacks
     */
    protected function runAfterCallbacks(?User $user, string $ability, bool $result, array $arguments): bool
    {
        foreach ($this->afterCallbacks as $callback) {
            $callbackResult = call_user_func($callback, $user, $ability, $result, ...$arguments);
            
            if ($callbackResult !== null) {
                return (bool) $callbackResult;
            }
        }

        return $result;
    }

    /**
     * Get policy class for model
     */
    protected function getPolicyFor(mixed $model): ?string
    {
        if (is_object($model)) {
            $modelClass = get_class($model);
            
            // Check exact match
            if (isset($this->policies[$modelClass])) {
                return $this->policies[$modelClass];
            }

            // Check parent classes
            foreach (class_parents($model) as $parent) {
                if (isset($this->policies[$parent])) {
                    return $this->policies[$parent];
                }
            }

            // Check interfaces
            foreach (class_implements($model) as $interface) {
                if (isset($this->policies[$interface])) {
                    return $this->policies[$interface];
                }
            }
        }

        return null;
    }

    /**
     * Get all defined abilities
     */
    public function getAbilities(): array
    {
        return $this->abilities;
    }

    /**
     * Get all registered policies
     */
    public function getPolicies(): array
    {
        return $this->policies;
    }

    /**
     * Authorize or throw exception
     */
    public function authorize(string $ability, mixed ...$arguments): void
    {
        if (!$this->check($ability, ...$arguments)) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Create a new policy instance for model
     */
    public function getPolicyInstanceForModel(mixed $model): mixed
    {
        $policyClass = $this->getPolicyFor($model);
        
        if ($policyClass === null) {
            throw new \RuntimeException("No policy found for model: " . get_class($model));
        }

        return app($policyClass);
    }
}
