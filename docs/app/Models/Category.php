<?php

declare(strict_types=1);

namespace App\Models;

use Zenith\Database\Model;

class Category extends Model
{
    protected static string $table = 'categories';
    protected array $fillable = [
        'name', 'slug', 'description', 'parent_id', 'icon', 'sort_order', 'is_active'
    ];
    protected array $casts = [
        'is_active' => 'bool',
        'parent_id' => 'int',
        'sort_order' => 'int',
    ];
    protected string $primaryKey = 'id';

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'category_id');
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', '=', $slug)->first();
    }

    public static function getTree(): array
    {
        $categories = static::where('is_active', '=', 1)->get();
        $tree = [];
        $indexed = [];

        foreach ($categories as $cat) {
            $indexed[$cat->id] = $cat;
            $indexed[$cat->id]->children = [];
        }

        foreach ($indexed as $id => $cat) {
            if ($cat->parent_id && isset($indexed[$cat->parent_id])) {
                $indexed[$cat->parent_id]->children[] = $cat;
            } else {
                $tree[] = $cat;
            }
        }

        return $tree;
    }
}
