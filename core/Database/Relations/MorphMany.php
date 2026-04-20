<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class MorphMany extends Relation
{
    protected string $morphType;
    protected string $morphId;
    protected string $localKey;

    public function __construct(Model $parent, Model $related, string $morphType, string $morphId, string $localKey, ?string $relationName = null)
    {
        $relationName = $relationName ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        parent::__construct($parent, $related, $relationName);
        $this->morphType = $morphType;
        $this->morphId = $morphId;
        $this->localKey = $localKey;
    }

    public function getResults(): array
    {
        return $this->getQuery()->get();
    }

    public function getQuery(): Builder
    {
        $localKeyValue = $this->parent->{$this->localKey};

        return $this->related->newQuery()
            ->where($this->morphId, $localKeyValue)
            ->where($this->morphType, get_class($this->parent));
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->localKey));
        $class = get_class($this->parent);

        if (!empty($keys)) {
            $this->related->newQuery()
                ->whereIn($this->morphId, $keys)
                ->where($this->morphType, $class);
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->morphId}][] = $result;
        }

        foreach ($models as $model) {
            $localKeyValue = $model->{$this->localKey};

            if (isset($dictionary[$localKeyValue])) {
                $model->setRelation($relation, $dictionary[$localKeyValue]);
            }
        }

        return $models;
    }

    public function create(array $attributes = []): Model
    {
        $attributes[$this->morphId] = $this->parent->{$this->localKey};
        $attributes[$this->morphType] = get_class($this->parent);
        return $this->related::create($attributes);
    }

    public function getMorphType(): string
    {
        return $this->morphType;
    }

    public function getMorphId(): string
    {
        return $this->morphId;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }
}
