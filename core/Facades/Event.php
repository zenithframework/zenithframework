<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static mixed dispatch(string $event, array $payload = [])
 * @method static void listen(string $event, \Closure|string|array $listener)
 * @method static void subscribe(object|string $subscriber)
 * @method static void forget(string $event)
 * @method static array getListeners(string $event)
 *
 * @see \Zenith\Event\EventDispatcher
 */
class Event extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Event\EventDispatcher::class;
    }
}
