<?php

declare(strict_types=1);

namespace Zenith\Database;

/**
 * Model Observer Base Class
 * 
 * Observers allow you to hook into model lifecycle events
 * such as creating, created, updating, updated, deleting, deleted, etc.
 */
abstract class Observer
{
    /**
     * Handle the "creating" event
     */
    public function creating(Model $model): void
    {
    }

    /**
     * Handle the "created" event
     */
    public function created(Model $model): void
    {
    }

    /**
     * Handle the "updating" event
     */
    public function updating(Model $model): void
    {
    }

    /**
     * Handle the "updated" event
     */
    public function updated(Model $model): void
    {
    }

    /**
     * Handle the "deleting" event
     */
    public function deleting(Model $model): void
    {
    }

    /**
     * Handle the "deleted" event
     */
    public function deleted(Model $model): void
    {
    }

    /**
     * Handle the "restoring" event (for soft deletes)
     */
    public function restoring(Model $model): void
    {
    }

    /**
     * Handle the "restored" event (for soft deletes)
     */
    public function restored(Model $model): void
    {
    }

    /**
     * Handle the "replicating" event
     */
    public function replicating(Model $model): void
    {
    }

    /**
     * Handle the "retrieved" event
     */
    public function retrieved(Model $model): void
    {
    }

    /**
     * Handle the "saving" event (before create or update)
     */
    public function saving(Model $model): void
    {
    }

    /**
     * Handle the "saved" event (after create or update)
     */
    public function saved(Model $model): void
    {
    }
}
