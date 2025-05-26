<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_snapshot',
        'customizations',
        'special_instructions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'product_snapshot' => 'array',
            'customizations' => 'array',
        ];
    }

    /**
     * Get the order that owns the order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that owns the order item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product name from snapshot or current product.
     */
    public function getProductNameAttribute(): string
    {
        $snapshot = $this->product_snapshot;
        if (isset($snapshot['name'])) {
            return $snapshot['name'];
        }
        
        return $this->product?->name ?? 'Unknown Product';
    }

    /**
     * Get the product image from snapshot or current product.
     */
    public function getProductImageAttribute(): ?string
    {
        $snapshot = $this->product_snapshot;
        if (isset($snapshot['image'])) {
            return $snapshot['image'];
        }
        
        return $this->product?->main_image_url;
    }
}
