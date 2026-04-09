<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class MorphToMany extends Relation
{
    protected string $name;
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected string $parentKey;
    protected string $relatedKey;
    protected bool $inverse;

    public function __construct(
        Model $parent,
        Model $related,
        string $name,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        bool $inverse = false,
        ?string $relationName = null
    ) {
        $relationName = $relationName ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        parent::__construct($parent, $related, $relationName);
        $this->name = $name;
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
        $this->inverse = $inverse;
    }

    public function getResults(): array
    {
        return $this->getQuery()->get();
    }

    public function getQuery(): Builder
    {
        $parentKeyValue = $this->parent->{$this->parentKey};
        $morphType = $this->inverse ? $this->relatedPivotKey : $this->foreignPivotKey;

        return $this->related->newQuery()
            ->join($this->table, $this->related->getTable() . '.' . $this->relatedKey, '=', $this->table . '.' . $this->relatedPivotKey)
            ->where($this->table . '.' . $this->foreignPivotKey, $parentKeyValue)
            ->where($this->table . '.' . $this->name . '_type', get_class($this->parent))
            ->select($this->related->getTable() . '.*');
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->parentKey));

        if (!empty($keys)) {
            $this->related->newQuery()
                ->join($this->table, $this->related->getTable() . '.' . $this->relatedKey, '=', $this->table . '.' . $this->relatedPivotKey)
                ->whereIn($this->table . '.' . $this->foreignPivotKey, $keys)
                ->where($this->table . '.' . $this->name . '_type', get_class($this->parent));
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->relatedKey}] = $result;
        }

        foreach ($models as $model) {
            $model->setRelation($relation, array_values($dictionary));
        }

        return $models;
    }

    public function attach(int|string|array $id, array $attributes = []): void
    {
        $ids = is_array($id) ? $id : [$id];

        $qb = new \Zenith\Database\QueryBuilder();

        foreach ($ids as $key => $value) {
            $insertData = [
                $this->foreignPivotKey => $this->parent->{$this->parentKey},
                $this->relatedPivotKey => is_array($id) ? $value : $id,
                $this->name . '_type' => get_class($this->parent),
            ];

            $insertData = array_merge($insertData, $attributes);
            $qb->table($this->table)->insert($insertData);
        }
    }

    public function detach(int|string|array|null $ids = null): void
    {
        $qb = new \Zenith\Database\QueryBuilder();
        $qb->table($this->table)->where($this->foreignPivotKey, $this->parent->{$this->parentKey});

        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            $qb->whereIn($this->relatedPivotKey, $ids);
        }

        $qb->delete();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function isInverse(): bool
    {
        return $this->inverse;
    }
}
