<?php

declare(strict_types=1);

namespace Zenith\Facades;

use Zenith\Facade;

/**
 * @method static \Zenith\Database\Schema create(string $table, \Closure $callback)
 * @method static \Zenith\Database\Schema table(string $table, \Closure $callback)
 * @method static \Zenith\Database\Schema drop(string $table)
 * @method static \Zenith\Database\Schema dropIfExists(string $table)
 * @method static \Zenith\Database\Schema rename(string $from, string $to)
 * @method static bool hasTable(string $table)
 * @method static bool hasColumn(string $table, string $column)
 * @method static array getColumnListing(string $table)
 * @method static array getTableListing()
 *
 * @see \Zenith\Database\Schema
 */
class Schema extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Zenith\Database\Schema::class;
    }
}
