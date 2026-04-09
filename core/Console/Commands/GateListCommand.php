<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Auth\Guards\Gate;

class GateListCommand extends Command
{
    protected string $name = 'gate:list';

    protected string $description = 'List all registered gates/abilities';

    public function handle(Container $container, array $arguments): void
    {
        try {
            $gate = $container->make(Gate::class);
        } catch (\Throwable $e) {
            $this->warn('Gate service is not available.');
            $this->line('Ensure Gate is configured in app/Providers/AuthServiceProvider.php');
            return;
        }

        $gates = $this->extractGates($gate);

        if (empty($gates)) {
            $this->warn('No gates registered.');
            $this->line('Define gates in app/Providers/AuthServiceProvider.php');
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Registered Gates/Abilities');
        $this->line(str_repeat('-', 80));

        foreach ($gates as $ability => $callback) {
            $this->line("  {$ability}");
        }

        $this->line(str_repeat('-', 80));
        $this->info("Total: " . count($gates) . " gate(s)");
    }

    protected function extractGates(Gate $gate): array
    {
        $reflection = new \ReflectionClass($gate);

        if ($reflection->hasProperty('abilities')) {
            $property = $reflection->getProperty('abilities');
            $property->setAccessible(true);
            return $property->getValue($gate) ?: [];
        }

        return [];
    }
}
