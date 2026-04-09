<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Category extends Model
{
    protected static string $table = 'categories';
    protected array $fillable = [
        'id', 'tenant_id', 'name', 'slug', 'description', 'parent_id',
        'image', 'sort_order', 'is_active', 'created_at', 'updated_at',
    ];
    protected array $casts = [
        'parent_id' => 'int',
        'sort_order' => 'int',
        'is_active' => 'bool',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::where('slug', $slug)->first();
    }

    public static function getActiveCategories(): array
    {
        return static::where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    public static function getTree(): array
    {
        $categories = static::getActiveCategories();
        return self::buildTree($categories);
    }

    protected static function buildTree(array $categories, int $parentId = 0): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = self::buildTree($categories, (int)$category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        return $tree;
    }

    public function getParent(): ?static
    {
        if (!$this->parent_id) {
            return null;
        }
        return static::find($this->parent_id);
    }

    public function getChildren(): array
    {
        return static::where('parent_id', $this->id)->get();
    }

    public function getProducts(): array
    {
        $qb = new \Zen\Database\QueryBuilder();
        $products = $qb->table('products')
            ->select('products.*')
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->where('category_product.category_id', $this->id)
            ->where('products.status', 'active')
            ->get();
        return $products;
    }

    public function hasChildren(): bool
    {
        return static::where('parent_id', $this->id)->count() > 0;
    }

    public function getProductCount(): int
    {
        $qb = new \Zen\Database\QueryBuilder();
        return $qb->table('category_product')
            ->where('category_id', $this->id)
            ->count();
    }
}
