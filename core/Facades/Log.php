<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static \Zenith\Log\Logger emergency(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger alert(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger critical(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger error(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger warning(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger notice(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger info(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger debug(string|\Stringable $message, array $context = [])
 * @method static \Zenith\Log\Logger log(string $level, string|\Stringable $message, array $context = [])
 *
 * @see \Zenith\Log\Logger
 */
class Log extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Log\Logger::class;
    }
}
