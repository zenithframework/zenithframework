<?php

declare(strict_types=1);

namespace Zenith\Database;

use Zenith\Database\Builder;
use Zenith\Database\QueryBuilder;
use Zenith\Database\Paginator;

abstract class Model
{
    protected static ?QueryBuilder $query = null;
    protected static string $table = '';
    protected array $attributes = [];
    protected array $original = [];
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected string $primaryKey = 'id';

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    public static function query(): Builder
    {
        return new Builder(static::class);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(int $id): ?static
    {
        return static::query()->find($id);
    }

    public static function findOrFail(int $id): static
    {
        $model = static::query()->find($id);

        if ($model === null) {
            throw new \RuntimeException("Model not found with ID {$id}");
        }

        return $model;
    }

    public static function where(string $column, mixed $operator, mixed $value = null): Builder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function updateOrCreate(array $match, array $values): static
    {
        $firstColumn = array_key_first($match);
        $firstValue = $match[$firstColumn];
        $model = static::query()->where($firstColumn, $firstValue)->first();

        if ($model === null) {
            return static::create(array_merge($match, $values));
        }

        foreach ($values as $key => $value) {
            $model->{$key} = $value;
        }

        $model->save();
        return $model;
    }

    public static function firstWhere(string $column, mixed $operator, mixed $value = null): ?static
    {
        return static::query()->where($column, $operator, $value)->first();
    }

    public static function count(): int
    {
        return static::query()->count();
    }

    public static function paginate(int $perPage = 15): Paginator
    {
        return static::query()->paginate($perPage);
    }

    public static function table(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        $class = basename(str_replace('\\', '/', static::class));
        return strtolower($class) . 's';
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (empty($this->fillable)) {
            return true;
        }

        return in_array($key, $this->fillable);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }

        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        if (!isset($this->attributes[$key])) {
            return null;
        }

        $value = $this->attributes[$key];

        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }

        return $value;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double', 'real' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array', 'json' => json_decode($value, true),
            'object', 'json_object' => json_decode($value),
            default => $value,
        };
    }

    public function save(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');

            if (!isset($this->attributes['created_at'])) {
                $this->attributes['created_at'] = $now;
            }

            $this->attributes['updated_at'] = $now;
        }

        $qb = new QueryBuilder();
        $qb->table(static::table());

        if (isset($this->attributes[$this->primaryKey])) {
            $qb->where($this->primaryKey, $this->attributes[$this->primaryKey]);
            return $qb->update($this->getDirty()) > 0;
        }

        $qb->insert($this->attributes);
        $this->attributes[$this->primaryKey] = (int) $qb->lastInsertId();

        return true;
    }

    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $qb = new QueryBuilder();
        return $qb->table(static::table())
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->delete() > 0;
    }

    public function toArray(): array
    {
        $array = $this->attributes;

        foreach ($this->hidden as $key) {
            unset($array[$key]);
        }

        foreach ($this->casts as $key => $cast) {
            if (isset($array[$key])) {
                $array[$key] = $this->castAttribute($key, $array[$key]);
            }
        }

        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function fresh(): static
    {
        return static::findOrFail((int) $this->attributes[$this->primaryKey]);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getHidden(): array
    {
        return $this->hidden;
    }

    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }
}
