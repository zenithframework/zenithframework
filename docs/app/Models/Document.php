<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Document extends Model
{
    protected static string $table = 'documents';
    protected array $fillable = [
        'title', 'slug', 'content', 'category_id', 'author_id', 
        'version', 'tags', 'status', 'views', 'sort_order'
    ];
    protected array $hidden = [];
    protected array $casts = [
        'tags' => 'json',
        'category_id' => 'int',
        'author_id' => 'int',
        'views' => 'int',
        'sort_order' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', '=', $slug)->first();
    }

    public static function getPublished(): array
    {
        return static::where('status', '=', 'published')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function incrementViews(): void
    {
        $this->views = ($this->views ?? 0) + 1;
        $this->save();
    }

    public function getTagsArray(): array
    {
        return is_array($this->tags) ? $this->tags : json_decode($this->tags ?? '[]', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
