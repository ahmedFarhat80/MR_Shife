<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'merchant_id',
        'category_id',
        'name',
        'description',
        'price',
        'discount_price',
        'sku',
        'images',
        'preparation_time',
        'calories',
        'ingredients',
        'allergens',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'is_spicy',
        'is_available',
        'is_featured',
        'sort_order',
        'stock_quantity',
        'track_stock',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
        'ingredients',
        'allergens',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'images' => 'array',
            'ingredients' => 'array',
            'allergens' => 'array',
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'is_spicy' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'track_stock' => 'boolean',
            'preparation_time' => 'integer',
            'calories' => 'integer',
            'sort_order' => 'integer',
            'stock_quantity' => 'integer',
        ];
    }

    /**
     * Get the merchant that owns the product.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope a query to only include available products.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the product's main image URL.
     */
    public function getMainImageUrlAttribute(): ?string
    {
        $images = $this->images;
        if (is_array($images) && count($images) > 0) {
            return asset('storage/' . $images[0]);
        }
        return null;
    }

    /**
     * Get all product image URLs.
     */
    public function getImageUrlsAttribute(): array
    {
        $images = $this->images;
        if (is_array($images)) {
            return array_map(fn($image) => asset('storage/' . $image), $images);
        }
        return [];
    }

    /**
     * Get the effective price (discount price if available, otherwise regular price).
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    /**
     * Check if product has discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null && $this->discount_price < $this->price;
    }

    /**
     * Check if product is in stock.
     */
    public function getInStockAttribute(): bool
    {
        if (!$this->track_stock) {
            return true;
        }
        return $this->stock_quantity > 0;
    }

    /**
     * Decrease stock quantity.
     */
    public function decreaseStock(int $quantity): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $this->decrement('stock_quantity', $quantity);
        return true;
    }

    /**
     * Increase stock quantity.
     */
    public function increaseStock(int $quantity): void
    {
        if ($this->track_stock) {
            $this->increment('stock_quantity', $quantity);
        }
    }
}
