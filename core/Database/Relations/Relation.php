<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

abstract class Relation
{
    protected Model $parent;
    protected Model $related;
    protected string $relationName;

    public function __construct(Model $parent, Model $related, string $relationName)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->relationName = $relationName;
    }

    abstract public function getResults(): mixed;

    abstract public function getQuery(): Builder;

    public function getRelationName(): string
    {
        return $this->relationName;
    }

    public function getParent(): Model
    {
        return $this->parent;
    }

    public function getRelated(): Model
    {
        return $this->related;
    }

    public function addEagerConstraints(array $models): void
    {
        // Override in child classes
    }

    public function match(array $models, array $results, string $relation): array
    {
        // Override in child classes
        return $models;
    }

    public function initRelation(array $models): array
    {
        // Override in child classes
        return $models;
    }
}
