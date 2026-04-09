<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class HasOne extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $localKey, ?string $relationName = null)
    {
        $relationName = $relationName ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        parent::__construct($parent, $related, $relationName);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getResults(): mixed
    {
        $localKeyValue = $this->parent->{$this->localKey};

        if ($localKeyValue === null) {
            return null;
        }

        return $this->getQuery()->first();
    }

    public function getQuery(): Builder
    {
        $localKeyValue = $this->parent->{$this->localKey};

        return $this->related->newQuery()
            ->where($this->foreignKey, $localKeyValue);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->localKey));

        if (!empty($keys)) {
            $this->related->newQuery()->whereIn($this->foreignKey, $keys);
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}] = $result;
        }

        foreach ($models as $model) {
            $localKeyValue = $model->{$this->localKey};

            if (isset($dictionary[$localKeyValue])) {
                $model->setRelation($relation, $dictionary[$localKeyValue]);
            }
        }

        return $models;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $this->related->newQuery()->where($column, $operator, $value);
        return $this;
    }

    public function create(array $attributes = []): Model
    {
        $attributes[$this->foreignKey] = $this->parent->{$this->localKey};
        return $this->related::create($attributes);
    }
}
