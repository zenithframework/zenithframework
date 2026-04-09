<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class CartItem extends Model
{
    protected static string $table = 'cart_items';
    protected array $fillable = [
        'id', 'cart_id', 'product_id', 'quantity', 'price', 'created_at',
    ];
    protected array $casts = [
        'cart_id' => 'int',
        'product_id' => 'int',
        'quantity' => 'int',
        'price' => 'float',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public function getTotal(): float
    {
        return round($this->price * $this->quantity, 2);
    }

    public function getProduct(): ?Product
    {
        return Product::find($this->product_id);
    }
}
