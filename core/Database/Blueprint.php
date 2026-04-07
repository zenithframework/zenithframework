<?php

declare(strict_types=1);

namespace Zen\Database;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreignKeys = [];
    protected array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function id(): static
    {
        $this->columns[] = [
            'name' => 'id',
            'type' => 'integer',
            'primary' => true,
            'auto_increment' => true,
        ];
        return $this;
    }

    public function bigId(): static
    {
        $this->columns[] = [
            'name' => 'id',
            'type' => 'bigint',
            'primary' => true,
            'auto_increment' => true,
        ];
        return $this;
    }

    public function unsignedId(): static
    {
        $this->columns[] = [
            'name' => 'id',
            'type' => 'unsigned_bigint',
            'primary' => true,
            'auto_increment' => true,
        ];
        return $this;
    }

    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'varchar',
            'length' => $length,
        ];
        return $this;
    }

    public function text(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'text',
        ];
        return $this;
    }

    public function longText(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'longtext',
        ];
        return $this;
    }

    public function mediumText(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'mediumtext',
        ];
        return $this;
    }

    public function tinyText(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'tinytext',
        ];
        return $this;
    }

    public function integer(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'integer',
        ];
        return $this;
    }

    public function tinyInteger(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'tinyint',
        ];
        return $this;
    }

    public function smallInteger(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'smallint',
        ];
        return $this;
    }

    public function bigInteger(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'bigint',
        ];
        return $this;
    }

    public function unsignedBigInteger(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'unsigned_bigint',
        ];
        return $this;
    }

    public function unsignedInteger(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'unsigned_integer',
        ];
        return $this;
    }

    public function float(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'float',
        ];
        return $this;
    }

    public function double(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'double',
        ];
        return $this;
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'decimal',
            'precision' => $precision,
            'scale' => $scale,
        ];
        return $this;
    }

    public function boolean(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'boolean',
        ];
        return $this;
    }

    public function date(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'date',
        ];
        return $this;
    }

    public function datetime(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'datetime',
        ];
        return $this;
    }

    public function timestamp(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'timestamp',
        ];
        return $this;
    }

    public function time(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'time',
        ];
        return $this;
    }

    public function year(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'year',
        ];
        return $this;
    }

    public function binary(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'binary',
        ];
        return $this;
    }

    public function json(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'json',
        ];
        return $this;
    }

    public function jsonb(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'jsonb',
        ];
        return $this;
    }

    public function uuid(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'uuid',
        ];
        return $this;
    }

    public function ipAddress(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'ip_address',
        ];
        return $this;
    }

    public function macAddress(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'mac_address',
        ];
        return $this;
    }

    public function enum(string $name, array $allowed): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'enum',
            'allowed' => $allowed,
        ];
        return $this;
    }

    public function set(string $name, array $allowed): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'set',
            'allowed' => $allowed,
        ];
        return $this;
    }

    public function foreignId(string $name): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => 'unsigned_bigint',
            'foreign' => true,
        ];
        return $this;
    }

    public function foreignIdFor(string $model, ?string $name = null): static
    {
        $columnName = $name ?? (strtolower(basename(str_replace('\\', '/', $model))) . '_id');
        $this->columns[] = [
            'name' => $columnName,
            'type' => 'unsigned_bigint',
            'foreign' => true,
        ];
        return $this;
    }

    public function morphs(string $name): static
    {
        $this->columns[] = [
            'name' => $name . '_id',
            'type' => 'unsigned_bigint',
            'index' => true,
        ];
        $this->columns[] = [
            'name' => $name . '_type',
            'type' => 'string',
            'length' => 255,
            'index' => true,
        ];
        return $this;
    }

    public function nullableMorphs(string $name): static
    {
        $this->columns[] = [
            'name' => $name . '_id',
            'type' => 'unsigned_bigint',
            'nullable' => true,
            'index' => true,
        ];
        $this->columns[] = [
            'name' => $name . '_type',
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'index' => true,
        ];
        return $this;
    }

    public function timestamps(): static
    {
        $this->columns[] = [
            'name' => 'created_at',
            'type' => 'timestamp',
            'nullable' => true,
        ];
        $this->columns[] = [
            'name' => 'updated_at',
            'type' => 'timestamp',
            'nullable' => true,
        ];
        return $this;
    }

    public function softDeletes(string $column = 'deleted_at'): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'timestamp',
            'nullable' => true,
        ];
        return $this;
    }

    public function softDeletesTz(string $column = 'deleted_at'): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'timestamp',
            'nullable' => true,
        ];
        return $this;
    }

    public function rememberToken(): static
    {
        $this->columns[] = [
            'name' => 'remember_token',
            'type' => 'string',
            'length' => 100,
            'nullable' => true,
        ];
        return $this;
    }

    public function uuidMorphs(string $name): static
    {
        $this->columns[] = [
            'name' => $name . '_id',
            'type' => 'uuid',
            'index' => true,
        ];
        $this->columns[] = [
            'name' => $name . '_type',
            'type' => 'string',
            'length' => 255,
            'index' => true,
        ];
        return $this;
    }

    public function nullableUuidMorphs(string $name): static
    {
        $this->columns[] = [
            'name' => $name . '_id',
            'type' => 'uuid',
            'nullable' => true,
            'index' => true,
        ];
        $this->columns[] = [
            'name' => $name . '_type',
            'type' => 'string',
            'length' => 255,
            'nullable' => true,
            'index' => true,
        ];
        return $this;
    }

    public function index(array|string $columns, ?string $name = null): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->indexes[] = [
            'columns' => $columns,
            'name' => $name ?? $this->createIndexName('index', $columns),
            'type' => 'index',
        ];
        return $this;
    }

    public function unique(array|string $columns, ?string $name = null): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->indexes[] = [
            'columns' => $columns,
            'name' => $name ?? $this->createIndexName('unique', $columns),
            'type' => 'unique',
        ];
        return $this;
    }

    public function primary(array|string $columns, ?string $name = null): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->indexes[] = [
            'columns' => $columns,
            'name' => $name ?? $this->createIndexName('primary', $columns),
            'type' => 'primary',
        ];
        return $this;
    }

    public function fullText(array|string $columns, ?string $name = null): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->indexes[] = [
            'columns' => $columns,
            'name' => $name ?? $this->createIndexName('fulltext', $columns),
            'type' => 'fulltext',
        ];
        return $this;
    }

    public function spatialIndex(array|string $columns, ?string $name = null): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->indexes[] = [
            'columns' => $columns,
            'name' => $name ?? $this->createIndexName('spatial_index', $columns),
            'type' => 'spatial_index',
        ];
        return $this;
    }

    public function foreign(string $column): ForeignIdColumn
    {
        return new ForeignIdColumn($this, $column);
    }

    public function addColumn(string $type, string $name, array $attributes = []): static
    {
        $this->columns[] = array_merge([
            'name' => $name,
            'type' => $type,
        ], $attributes);
        return $this;
    }

    public function dropColumn(string|array $columns): static
    {
        $columns = is_string($columns) ? [$columns] : $columns;
        $this->commands[] = [
            'type' => 'dropColumn',
            'columns' => $columns,
        ];
        return $this;
    }

    public function renameColumn(string $from, string $to): static
    {
        $this->commands[] = [
            'type' => 'renameColumn',
            'from' => $from,
            'to' => $to,
        ];
        return $this;
    }

    public function change(): Column
    {
        return new Column($this, 'change');
    }

    protected function createIndexName(string $type, array $columns): string
    {
        $table = $this->table;
        $columns = implode('_', $columns);
        return "{$table}_{$columns}_{$type}";
    }
}

class ForeignIdColumn
{
    protected Blueprint $blueprint;
    protected string $column;
    protected ?string $onDelete = null;
    protected ?string $onUpdate = null;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function constrained(?string $table = null, ?string $column = null): static
    {
        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function restrictOnDelete(): static
    {
        $this->onDelete = 'restrict';
        return $this;
    }

    public function cascadeOnDelete(): static
    {
        $this->onDelete = 'cascade';
        return $this;
    }

    public function setNullOnDelete(): static
    {
        $this->onDelete = 'set null';
        return $this;
    }

    public function noActionOnDelete(): static
    {
        $this->onDelete = 'no action';
        return $this;
    }

    public function restrictOnUpdate(): static
    {
        $this->onUpdate = 'restrict';
        return $this;
    }

    public function cascadeOnUpdate(): static
    {
        $this->onUpdate = 'cascade';
        return $this;
    }

    public function setNullOnUpdate(): static
    {
        $this->onUpdate = 'set null';
        return $this;
    }

    public function noActionOnUpdate(): static
    {
        $this->onUpdate = 'no action';
        return $this;
    }

    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }
}

class Column
{
    protected Blueprint $blueprint;
    protected string $name;
    protected string $type;
    protected array $modifiers = [];

    public function __construct(Blueprint $blueprint, string $name, string $type = 'string')
    {
        $this->blueprint = $blueprint;
        $this->name = $name;
        $this->type = $type;
    }

    public function nullable(): static
    {
        $this->modifiers[] = 'nullable';
        return $this;
    }

    public function default(mixed $value): static
    {
        $this->modifiers['default'] = $value;
        return $this;
    }

    public function useCurrent(): static
    {
        $this->modifiers[] = 'useCurrent';
        return $this;
    }

    public function useCurrentOnUpdate(): static
    {
        $this->modifiers[] = 'useCurrentOnUpdate';
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->modifiers[] = 'autoIncrement';
        return $this;
    }

    public function unsigned(): static
    {
        $this->modifiers[] = 'unsigned';
        return $this;
    }

    public function unique(): static
    {
        $this->modifiers[] = 'unique';
        return $this;
    }

    public function index(?string $name = null): static
    {
        $this->modifiers['index'] = $name;
        return $this;
    }

    public function primary(): static
    {
        $this->modifiers[] = 'primary';
        return $this;
    }

    public function comment(string $comment): static
    {
        $this->modifiers['comment'] = $comment;
        return $this;
    }

    public function collation(string $collation): static
    {
        $this->modifiers['collation'] = $collation;
        return $this;
    }

    public function storedAs(string $expression): static
    {
        $this->modifiers['storedAs'] = $expression;
        return $this;
    }

    public function virtualAs(string $expression): static
    {
        $this->modifiers['virtualAs'] = $expression;
        return $this;
    }

    public function persisted(): static
    {
        $this->modifiers[] = 'persisted';
        return $this;
    }

    public function change(): static
    {
        $this->modifiers[] = 'change';
        return $this;
    }

    public function getModifiers(): array
    {
        return $this->modifiers;
    }
}
