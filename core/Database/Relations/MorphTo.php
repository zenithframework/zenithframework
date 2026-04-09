<?php

declare(strict_types=1);

namespace Zenith\Database\Relations;

use Zenith\Database\Model;
use Zenith\Database\Builder;

class MorphTo extends Relation
{
    protected string $morphType;
    protected string $morphId;
    protected ?string $ownerKey;

    public function __construct(Model $parent, string $morphType, string $morphId, ?string $ownerKey = null, ?string $relationName = null)
    {
        $relationName = $relationName ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $related = new Model(); // Placeholder
        parent::__construct($parent, $related, $relationName);
        $this->morphType = $morphType;
        $this->morphId = $morphId;
        $this->ownerKey = $ownerKey;
    }

    public function getResults(): mixed
    {
        $type = $this->parent->{$this->morphType};
        $id = $this->parent->{$this->morphId};

        if ($type === null || $id === null) {
            return null;
        }

        $model = new $type();
        $ownerKey = $this->ownerKey ?? $model->getKeyName();

        return $model->newQuery()->where($ownerKey, $id)->first();
    }

    public function getQuery(): Builder
    {
        $type = $this->parent->{$this->morphType};
        $id = $this->parent->{$this->morphId};

        $model = new $type();
        $ownerKey = $this->ownerKey ?? $model->getKeyName();

        return $model->newQuery()->where($ownerKey, $id);
    }

    public function getMorphType(): string
    {
        return $this->morphType;
    }

    public function getMorphId(): string
    {
        return $this->morphId;
    }
}
