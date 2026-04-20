<?php

declare(strict_types=1);

namespace Zenith\Database\Traits;

/**
 * Soft Deletes Trait
 * 
 * Provides soft delete functionality for models.
 * Instead of removing records, sets a deleted_at timestamp.
 */
trait SoftDeletes
{
    /**
     * Indicates if the model is using soft deletes
     */
    public bool $softDeletes = true;

    /**
     * The "deleted at" column name
     */
    protected string $deletedAtColumn = 'deleted_at';

    /**
     * Boot the soft delete trait
     */
    public static function bootSoftDeletes(): void
    {
        static::addGlobalScope('soft_deletes', function ($builder) {
            $builder->whereNull((new static())->getDeletedAtColumn());
        });
    }

    /**
     * Initialize the soft delete trait
     */
    public function initializeSoftDeletes(): void
    {
        $this->casts[$this->getDeletedAtColumn()] = 'datetime';
    }

    /**
     * Perform soft delete
     */
    public function delete(): bool
    {
        if ($this->softDeletes) {
            return $this->runSoftDelete();
        }

        return parent::delete();
    }

    /**
     * Perform the actual soft delete
     */
    protected function runSoftDelete(): bool
    {
        $column = $this->getDeletedAtColumn();
        $this->{$column} = $this->freshTimestamp();
        
        $this->exists = true;
        
        return $this->save();
    }

    /**
     * Restore a soft-deleted model
     */
    public function restore(): bool
    {
        if (!$this->softDeletes) {
            return false;
        }

        $column = $this->getDeletedAtColumn();
        $this->{$column} = null;
        
        return $this->save();
    }

    /**
     * Determine if the model has been soft-deleted
     */
    public function trashed(): bool
    {
        return $this->{$this->getDeletedAtColumn()} !== null;
    }

    /**
     * Get the "deleted at" column name
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    /**
     * Get a new query builder that includes soft deletes
     */
    public static function withTrashed(): static
    {
        $instance = new static();
        $query = $instance->newQuery();
        $query->withoutGlobalScope('soft_deletes');
        return $query;
    }

    /**
     * Get a new query builder that only includes soft deletes
     */
    public static function onlyTrashed(): static
    {
        $instance = new static();
        $query = $instance->newQuery();
        $query->withoutGlobalScope('soft_deletes');
        $query->whereNotNull($instance->getDeletedAtColumn());
        return $query;
    }

    /**
     * Permanently delete the model
     */
    public function forceDelete(): bool
    {
        return parent::delete();
    }

    /**
     * Restore soft-deleted models by query
     */
    public static function restoreTrashed(): int
    {
        $instance = new static();
        $column = $instance->getDeletedAtColumn();
        
        return $instance->newQuery()
            ->withoutGlobalScope('soft_deletes')
            ->whereNotNull($column)
            ->update([$column => null]);
    }
}
