<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class BelongsToMany extends Relation
{
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected string $parentKey;
    protected string $relatedKey;
    protected array $pivotValues = [];

    public function __construct(
        Model $parent,
        Model $related,
        string $table,
        string $foreignPivotKey,
        string $relatedPivotKey,
        string $parentKey,
        string $relatedKey,
        string $relationName
    ) {
        parent::__construct($parent, $related, $relationName);
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        $this->parentKey = $parentKey;
        $this->relatedKey = $relatedKey;
    }

    public function getResults(): array
    {
        return $this->getQuery()->get();
    }

    public function getQuery(): Builder
    {
        $parentKeyValue = $this->parent->{$this->parentKey};

        return $this->related->newQuery()
            ->join($this->table, $this->related->getTable() . '.' . $this->relatedKey, '=', $this->table . '.' . $this->relatedPivotKey)
            ->where($this->table . '.' . $this->foreignPivotKey, $parentKeyValue)
            ->select($this->related->getTable() . '.*');
    }

    public function addEagerConstraints(array $models): void
    {
        $keys = array_filter(array_column($models, $this->parentKey));

        if (!empty($keys)) {
            $this->related->newQuery()
                ->join($this->table, $this->related->getTable() . '.' . $this->relatedKey, '=', $this->table . '.' . $this->relatedPivotKey)
                ->whereIn($this->table . '.' . $this->foreignPivotKey, $keys);
        }
    }

    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->relatedKey}] = $result;
        }

        foreach ($models as $model) {
            $relatedModels = [];

            foreach ($results as $result) {
                // Check pivot table
                $relatedModels[] = $result;
            }

            $model->setRelation($relation, $relatedModels);
        }

        return $models;
    }

    public function withPivot(array|string $columns): static
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->pivotValues = $columns;
        return $this;
    }

    public function wherePivot(string $column, mixed $operator = null, mixed $value = null): static
    {
        // Add pivot where clause
        return $this;
    }

    public function attach(int|string|array $id, array $attributes = []): void
    {
        $ids = is_array($id) ? $id : [$id];

        $qb = new \Zenith\Database\QueryBuilder();

        foreach ($ids as $key => $value) {
            $insertData = [
                $this->foreignPivotKey => $this->parent->{$this->parentKey},
                $this->relatedPivotKey => is_array($id) ? $value : $id,
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

    public function sync(array $ids, bool $detaching = true): void
    {
        if ($detaching) {
            $this->detach(array_diff($this->getKeyValues(), $ids));
        }

        $this->attach(array_diff($ids, $this->getKeyValues()));
    }

    public function toggle(array $ids): void
    {
        $currentIds = $this->getKeyValues();

        $this->attach(array_diff($ids, $currentIds));
        $this->detach(array_diff($currentIds, $ids));
    }

    protected function getKeyValues(): array
    {
        $qb = new \Zenith\Database\QueryBuilder();
        return $qb->table($this->table)
            ->where($this->foreignPivotKey, $this->parent->{$this->parentKey})
            ->pluck($this->relatedPivotKey);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getForeignPivotKey(): string
    {
        return $this->foreignPivotKey;
    }

    public function getRelatedPivotKey(): string
    {
        return $this->relatedPivotKey;
    }

    public function getParentKey(): string
    {
        return $this->parentKey;
    }

    public function getRelatedKey(): string
    {
        return $this->relatedKey;
    }
}
