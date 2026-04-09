<?php

declare(strict_types=1);

namespace App\Models;

use Zen\Database\Model;

class Order extends Model
{
    protected static string $table = 'orders';
    protected array $fillable = [
        'id', 'tenant_id', 'order_number', 'user_id', 'status', 'payment_status',
        'payment_method', 'payment_id', 'subtotal', 'tax', 'shipping', 'discount',
        'total', 'currency', 'shipping_first_name', 'shipping_last_name', 'shipping_email',
        'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_state',
        'shipping_zip', 'shipping_country', 'billing_first_name', 'billing_last_name',
        'billing_email', 'billing_phone', 'billing_address', 'billing_city',
        'billing_state', 'billing_zip', 'billing_country', 'notes',
        'created_at', 'updated_at',
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'user_id' => 'int',
        'subtotal' => 'float',
        'tax' => 'float',
        'shipping' => 'float',
        'discount' => 'float',
        'total' => 'float',
    ];
    protected string $primaryKey = 'id';

    public static function boot(): void
    {
        parent::boot();
    }

    public static function findByOrderNumber(string $orderNumber): ?static
    {
        return static::where('order_number', $orderNumber)->first();
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 8));
    }

    public static function createOrder(array $data): static
    {
        $data['order_number'] = self::generateOrderNumber();
        
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        
        if (empty($data['payment_status'])) {
            $data['payment_status'] = 'pending';
        }
        
        if (empty($data['currency'])) {
            $data['currency'] = 'USD';
        }

        return static::create($data);
    }

    public function addItems(array $items): bool
    {
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $this->id,
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'] ?? null,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['total'],
            ]);
        }
        return true;
    }

    public function getItems(): array
    {
        return OrderItem::where('order_id', $this->id)->get();
    }

    public function markAsPaid(): bool
    {
        $this->payment_status = 'paid';
        $this->status = 'processing';
        return $this->save();
    }

    public function markAsShipped(): bool
    {
        $this->status = 'shipped';
        return $this->save();
    }

    public function markAsDelivered(): bool
    {
        $this->status = 'delivered';
        return $this->save();
    }

    public function markAsCancelled(): bool
    {
        $this->status = 'cancelled';
        return $this->save();
    }

    public function markAsRefunded(): bool
    {
        $this->payment_status = 'refunded';
        return $this->save();
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match($this->payment_status) {
            'pending' => 'Pending',
            'paid' => 'Paid',
            'refunded' => 'Refunded',
            default => ucfirst($this->payment_status),
        };
    }
}
