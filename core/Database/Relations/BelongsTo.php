<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class BelongsTo extends Relation
{
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(Model $parent, Model $related, string $foreignKey, string $ownerKey, string $relationName)
    {
        parent::__construct($parent, $related, $relationName);
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function getResults(): mixed
    {
        $foreignKeyValue = $this->parent->{$this->foreignKey};

        if ($foreignKeyValue === null) {
            return null;
        }

        return $this->getQuery()->first();
    }

    public function getQuery(): Builder
    {
        $foreignKeyValue = $this->parent->{$this->foreignKey};

        return $this->related->newQuery()
            ->where($this->ownerKey, $foreignKeyValue);
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->foreignKey));

        if (!empty($keys)) {
            $this->related->newQuery()->whereIn($this->ownerKey, $keys);
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->ownerKey}] = $result;
        }

        foreach ($models as $model) {
            $foreignKeyValue = $model->{$this->foreignKey};

            if (isset($dictionary[$foreignKeyValue])) {
                $model->setRelation($relation, $dictionary[$foreignKeyValue]);
            }
        }

        return $models;
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getOwnerKey(): string
    {
        return $this->ownerKey;
    }
}
