<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Auth\Guards\Gate;

class PolicyListCommand extends Command
{
    protected string $name = 'policy:list';

    protected string $description = 'List all policies';

    public function handle(Container $container, array $arguments): void
    {
        try {
            $gate = $container->make(Gate::class);
        } catch (\Throwable $e) {
            $this->warn('Gate service is not available.');
            $this->line('Ensure Gate is configured in app/Providers/AuthServiceProvider.php');
            return;
        }

        $policies = $this->extractPolicies($gate);

        if (empty($policies)) {
            $this->warn('No policies registered.');
            $this->line('Define policies in app/Providers/AuthServiceProvider.php');
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Policies');
        $this->line(str_repeat('-', 80));

        foreach ($policies as $model => $policy) {
            $modelClass = is_string($model) ? $model : get_class($model);
            $policyClass = is_string($policy) ? $policy : get_class($policy);

            $this->line("  Model:  {$modelClass}");
            $this->line("  Policy: {$policyClass}");

            $abilities = $this->extractPolicyAbilities($policy);

            if (!empty($abilities)) {
                $this->line("  Abilities: " . implode(', ', $abilities));
            }

            $this->line('');
        }

        $this->line(str_repeat('-', 80));
        $this->info("Total: " . count($policies) . " policie(s)");
    }

    protected function extractPolicies(Gate $gate): array
    {
        $reflection = new \ReflectionClass($gate);

        if ($reflection->hasProperty('policies')) {
            $property = $reflection->getProperty('policies');
            $property->setAccessible(true);
            return $property->getValue($gate) ?: [];
        }

        return [];
    }

    protected function extractPolicyAbilities(mixed $policy): array
    {
        $abilities = [];

        if (is_string($policy) && class_exists($policy)) {
            $reflection = new \ReflectionClass($policy);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (!str_starts_with($method->getName(), '__')) {
                    $abilities[] = $method->getName();
                }
            }
        } elseif (is_object($policy)) {
            $reflection = new \ReflectionObject($policy);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (!str_starts_with($method->getName(), '__')) {
                    $abilities[] = $method->getName();
                }
            }
        }

        return $abilities;
    }
}
