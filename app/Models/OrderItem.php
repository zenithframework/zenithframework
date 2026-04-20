<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class OrderItem extends Model
{
    protected static string $table = 'order_items';
    protected array $fillable = [
        'id', 'order_id', 'product_id', 'product_name', 'product_sku',
        'quantity', 'price', 'total', 'created_at',
    ];
    protected array $casts = [
        'order_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'int',
        'price' => 'float',
        'total' => 'float',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public function getProduct(): ?Product
    {
        return Product::find($this->product_id);
    }
}
