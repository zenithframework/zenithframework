<?php

declare(strict_types=1);

namespace Zen\Database;

class Builder
{
    protected QueryBuilder $qb;
    protected string $modelClass;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->qb = new QueryBuilder();
        $this->qb->table($modelClass::table());
    }

    public function select(array $columns = ['*']): static
    {
        $this->qb->select($columns);
        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): static
    {
        $this->qb->where($column, $operator, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->qb->whereIn($column, $values);
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->qb->whereNull($column);
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->qb->whereNotNull($column);
        return $this;
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): static
    {
        $this->qb->orWhere($column, $operator, $value);
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->qb->join($table, $first, $operator, $second, $type);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->qb->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->qb->rightJoin($table, $first, $operator, $second);
        return $this;
    }

    public function groupBy(string $column): static
    {
        $this->qb->groupBy($column);
        return $this;
    }

    public function having(string $condition): static
    {
        $this->qb->having($condition);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->qb->orderBy($column, $direction);
        return $this;
    }

    public function latest(string $column = 'created_at'): static
    {
        $this->qb->latest($column);
        return $this;
    }

    public function oldest(string $column = 'created_at'): static
    {
        $this->qb->oldest($column);
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->qb->limit($limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->qb->offset($offset);
        return $this;
    }

    public function forPage(int $page, int $perPage): static
    {
        $this->qb->forPage($page, $perPage);
        return $this;
    }

    public function get(): array
    {
        $results = $this->qb->get();
        $modelClass = $this->modelClass;

        return array_map(function (array $item) use ($modelClass) {
            $model = new $modelClass();
            foreach ($item as $key => $value) {
                $model->{$key} = $value;
            }
            return $model;
        }, $results);
    }

    public function first(): ?object
    {
        $result = $this->qb->first();

        if ($result === false) {
            return null;
        }

        $modelClass = $this->modelClass;
        $model = new $modelClass();

        foreach ($result as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    public function find(int $id): ?object
    {
        return $this->where('id', $id)->first();
    }

    public function findOrFail(int $id): object
    {
        $model = $this->find($id);

        if ($model === null) {
            throw new \RuntimeException("Model not found with ID {$id}");
        }

        return $model;
    }

    public function pluck(string $column): array
    {
        return $this->qb->pluck($column);
    }

    public function count(string $column = '*'): int
    {
        return $this->qb->count($column);
    }

    public function exists(): bool
    {
        return $this->qb->exists();
    }

    public function max(string $column): mixed
    {
        return $this->qb->max($column);
    }

    public function min(string $column): mixed
    {
        return $this->qb->min($column);
    }

    public function avg(string $column): mixed
    {
        return $this->qb->avg($column);
    }

    public function sum(string $column): mixed
    {
        return $this->qb->sum($column);
    }

    public function paginate(int $perPage = 15, int $page = null): Paginator
    {
        return $this->qb->paginate($perPage, $page);
    }

    public function chunk(int $count, callable $callback): void
    {
        $this->qb->chunk($count, $callback);
    }

    public function update(array $data): int
    {
        return $this->qb->update($data);
    }

    public function delete(): int
    {
        return $this->qb->delete();
    }

    public function getQuery(): QueryBuilder
    {
        return $this->qb;
    }
}
