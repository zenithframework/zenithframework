<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Routing\RouteGroup;

class RouteGroupListCommand extends Command
{
    protected string $name = 'route:group';

    protected string $description = 'List route groups';

    public function handle(Container $container, array $arguments): void
    {
        $json = in_array('--json', $arguments);
        $router = $container->make(\Zenith\Routing\Router::class);
        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->warn('No routes registered.');
            return;
        }

        $groups = $this->groupRoutes($routes);

        if ($json) {
            $output = [];

            foreach ($groups as $prefix => $groupRoutes) {
                $output[$prefix] = array_map(fn($r) => [
                    'method' => $r['method'],
                    'uri' => $r['uri'],
                    'name' => $r['name'],
                ], $groupRoutes);
            }

            echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
            return;
        }

        $this->line(str_repeat('-', 80));
        $this->info('Route Groups');
        $this->line(str_repeat('-', 80));

        foreach ($groups as $prefix => $groupRoutes) {
            $this->line('');
            $this->info("  Group: /{$prefix} (" . count($groupRoutes) . " routes)");

            foreach ($groupRoutes as $route) {
                $method = str_pad($route['method'], 8);
                $uri = $route['uri'];
                $name = $route['name'] ?? '(unnamed)';

                $this->line("    {$method} {$uri} - {$name}");
            }
        }

        $this->line('');
        $this->line(str_repeat('-', 80));
        $this->info('Total groups: ' . count($groups));
    }

    protected function groupRoutes(array $routes): array
    {
        $groups = [];

        foreach ($routes as $route) {
            $uri = $route->getUri();
            $segments = explode('/', trim($uri, '/'));
            $prefix = $segments[0] ?? '/';

            if (!isset($groups[$prefix])) {
                $groups[$prefix] = [];
            }

            $groups[$prefix][] = [
                'method' => $route->getMethod(),
                'uri' => $uri,
                'name' => $route->getName(),
            ];
        }

        ksort($groups);

        return $groups;
    }
}
