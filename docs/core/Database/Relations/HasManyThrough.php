<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class HasManyThrough extends Relation
{
    protected Model $through;
    protected string $firstKey;
    protected string $secondKey;
    protected string $localKey;
    protected string $secondLocalKey;

    public function __construct(
        Model $parent,
        Model $related,
        Model $through,
        string $firstKey,
        string $secondKey,
        string $localKey,
        string $secondLocalKey,
        ?string $relationName = null
    ) {
        $relationName = $relationName ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        parent::__construct($parent, $related, $relationName);
        $this->through = $through;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
        $this->localKey = $localKey;
        $this->secondLocalKey = $secondLocalKey;
    }

    public function getResults(): array
    {
        return $this->getQuery()->get();
    }

    public function getQuery(): Builder
    {
        $localKeyValue = $this->parent->{$this->localKey};

        return $this->related->newQuery()
            ->join(
                $this->through->getTable(),
                $this->through->getTable() . '.' . $this->secondKey,
                '=',
                $this->related->getTable() . '.' . $this->related->getKeyName()
            )
            ->where($this->through->getTable() . '.' . $this->firstKey, $localKeyValue)
            ->select($this->related->getTable() . '.*');
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->localKey));

        if (!empty($keys)) {
            $this->related->newQuery()
                ->join(
                    $this->through->getTable(),
                    $this->through->getTable() . '.' . $this->secondKey,
                    '=',
                    $this->related->getTable() . '.' . $this->related->getKeyName()
                )
                ->whereIn($this->through->getTable() . '.' . $this->firstKey, $keys);
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->related->getKeyName()}] = $result;
        }

        foreach ($models as $model) {
            $relatedModels = [];

            foreach ($results as $result) {
                $relatedModels[] = $result;
            }

            $model->setRelation($relation, $relatedModels);
        }

        return $models;
    }

    public function getThrough(): Model
    {
        return $this->through;
    }

    public function getFirstKey(): string
    {
        return $this->firstKey;
    }

    public function getSecondKey(): string
    {
        return $this->secondKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function getSecondLocalKey(): string
    {
        return $this->secondLocalKey;
    }
}
