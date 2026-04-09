<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class ProductImage extends Model
{
    protected static string $table = 'product_images';
    protected array $fillable = [
        'id', 'product_id', 'image_url', 'alt_text', 'sort_order', 'is_primary', 'created_at',
    ];
    protected array $casts = [
        'product_id' => 'int',
        'sort_order' => 'int',
        'is_primary' => 'bool',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }
}
