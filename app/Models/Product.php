<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Product extends Model
{
    protected static string $table = 'products';
    protected array $fillable = [
        'id', 'tenant_id', 'name', 'slug', 'description', 'short_description',
        'sku', 'price', 'compare_price', 'cost_price', 'quantity',
        'track_inventory', 'allow_backorder', 'weight', 'length', 'width', 'height',
        'status', 'is_featured', 'is_digital', 'download_url',
        'meta_title', 'meta_description', 'meta_keywords',
        'created_at', 'updated_at',
    ];
    protected array $casts = [
        'price' => 'float',
        'compare_price' => 'float',
        'cost_price' => 'float',
        'quantity' => 'int',
        'track_inventory' => 'bool',
        'allow_backorder' => 'bool',
        'weight' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'is_featured' => 'bool',
        'is_digital' => 'bool',
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

    public static function getFeatured(int $limit = 8): array
    {
        return static::where('is_featured', 1)->where('status', 'active')
            ->limit($limit)
            ->get();
    }

    public static function getActive(int $limit = 20): array
    {
        return static::where('status', 'active')
            ->limit($limit)
            ->get();
    }

    public function hasDiscount(): bool
    {
        return $this->compare_price && $this->compare_price > $this->price;
    }

    public function getDiscountPercent(): float
    {
        if (!$this->hasDiscount()) {
            return 0.0;
        }
        return round((($this->compare_price - $this->price) / $this->compare_price) * 100, 2);
    }

    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->quantity > 0 || $this->allow_backorder;
    }

    public function getPrimaryImage(): ?string
    {
        $images = $this->getImages();
        foreach ($images as $image) {
            if ($image['is_primary']) {
                return $image['image_url'];
            }
        }
        return $images[0]['image_url'] ?? null;
    }

    public function getImages(): array
    {
        $images = ProductImage::where('product_id', $this->id)
            ->orderBy('sort_order', 'ASC')
            ->get();
        return $images;
    }

    public function getCategories(): array
    {
        $qb = new \Zen\Database\QueryBuilder();
        $categories = $qb->table('categories')
            ->select('categories.*')
            ->join('category_product', 'categories.id', '=', 'category_product.category_id')
            ->where('category_product.product_id', $this->id)
            ->get();
        return $categories;
    }
}
