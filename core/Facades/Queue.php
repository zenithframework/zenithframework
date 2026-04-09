<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static \Zenith\Queue\QueueManager connection(string|null $name = null)
 * @method static mixed push(string|object $job, mixed $data = '', string $queue = null)
 * @method static mixed pushOn(string $queue, string|object $job, mixed $data = '')
 * @method static mixed later(\DateTimeInterface|\DateInterval|int $delay, string|object $job, mixed $data = '', string $queue = null)
 * @method static bool fail(\Throwable $e)
 * @method static int size(string $queue = null)
 *
 * @see \Zenith\Queue\QueueManager
 */
class Queue extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Queue\QueueManager::class;
    }
}
