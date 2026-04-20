<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Cache\Cache;

class CacheForgetCommand extends Command
{
    protected string $name = 'cache:forget';

    protected string $description = 'Remove specific cache key';

    public function handle(Container $container, array $arguments): void
    {
        $key = $arguments[0] ?? null;

        if ($key === null) {
            $key = $this->ask('Enter cache key to remove');
        }

        if (empty($key)) {
            $this->error('Cache key is required.');
            return;
        }

        $normalizedKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $cachePath = Cache::getPath();
        $cacheFile = $cachePath . '/' . $normalizedKey . '.cache';

        if (!file_exists($cacheFile)) {
            $this->warn("Cache key [{$key}] does not exist.");
            return;
        }

        $result = Cache::forget($key);

        if ($result) {
            $this->info("Cache key [{$key}] removed successfully.");
        } else {
            $this->error("Failed to remove cache key [{$key}].");
        }
    }
}
