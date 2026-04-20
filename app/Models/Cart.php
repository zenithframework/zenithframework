<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Cart extends Model
{
    protected static string $table = 'carts';
    protected array $fillable = [
        'id', 'tenant_id', 'user_id', 'session_id', 'created_at', 'updated_at',
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'user_id' => 'int',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public static function findBySession(string $sessionId): ?static
    {
        return static::where('session_id', $sessionId)->first();
    }

    public static function findByUser(int $userId): ?static
    {
        return static::where('user_id', $userId)->first();
    }

    public static function createForSession(string $sessionId, int $tenantId): static
    {
        return static::create([
            'session_id' => $sessionId,
            'tenant_id' => $tenantId,
        ]);
    }

    public static function createForUser(int $userId, int $tenantId): static
    {
        return static::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ]);
    }

    public function addItem(int $productId, int $quantity = 1): bool
    {
        $product = Product::find($productId);
        if (!$product || !$product->isInStock()) {
            return false;
        }

        // Check if item already exists in cart
        $qb = new \Zen\Database\QueryBuilder();
        $existingItem = $qb->table('cart_items')
            ->where('cart_id', $this->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            return $qb->table('cart_items')
                ->where('id', $existingItem['id'])
                ->update(['quantity' => $newQuantity]) > 0;
        }

        // Add new item
        return CartItem::create([
            'cart_id' => $this->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product->price,
        ]) !== null;
    }

    public function removeItem(int $productId): bool
    {
        $qb = new \Zen\Database\QueryBuilder();
        return $qb->table('cart_items')
            ->where('cart_id', $this->id)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    public function updateItemQuantity(int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($productId);
        }

        $qb = new \Zen\Database\QueryBuilder();
        return $qb->table('cart_items')
            ->where('cart_id', $this->id)
            ->where('product_id', $productId)
            ->update(['quantity' => $quantity]) > 0;
    }

    public function getItems(): array
    {
        $qb = new \Zen\Database\QueryBuilder();
        $items = $qb->table('cart_items')
            ->select('cart_items.*', 'products.name as product_name', 'products.slug as product_slug', 'products.sku as product_sku')
            ->join('products', 'cart_items.product_id', '=', 'products.id')
            ->where('cart_items.cart_id', $this->id)
            ->get();

        return $items;
    }

    public function getSubtotal(): float
    {
        $items = $this->getItems();
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float)$item['price'] * $item['quantity'];
        }

        return round($subtotal, 2);
    }

    public function getItemCount(): int
    {
        $qb = new \Zen\Database\QueryBuilder();
        return (int)$qb->table('cart_items')
            ->where('cart_id', $this->id)
            ->sum('quantity');
    }

    public function clear(): bool
    {
        $qb = new \Zen\Database\QueryBuilder();
        return $qb->table('cart_items')
            ->where('cart_id', $this->id)
            ->delete() > 0;
    }
}
