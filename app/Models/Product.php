<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;
use App\Helpers\ImageHelper;

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
        'internal_category_id',
        'sub_category_id',
        'food_nationality_id',
        'name',
        'description',
        'background_type',
        'background_value',
        'base_price',
        'discount_percentage',
        'discounted_price',
        'is_available',
        'preparation_time',
        'sku',
        'calories',
        'ingredients',
        'allergens',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'is_spicy',
        'is_featured',
        'is_popular',
        'total_orders',
        'average_rating',
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
            'base_price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'ingredients' => 'array',
            'allergens' => 'array',
            'is_vegetarian' => 'boolean',
            'is_vegan' => 'boolean',
            'is_gluten_free' => 'boolean',
            'is_spicy' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',
            'track_stock' => 'boolean',
            'total_orders' => 'integer',
            'average_rating' => 'decimal:2',
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
     * Get the internal category that owns the product.
     */
    public function internalCategory(): BelongsTo
    {
        return $this->belongsTo(InternalCategory::class);
    }

    /**
     * Alias for internalCategory relationship (for backward compatibility).
     */
    public function internal_category(): BelongsTo
    {
        return $this->internalCategory();
    }

    /**
     * Get the food nationality that owns the product.
     */
    public function foodNationality(): BelongsTo
    {
        return $this->belongsTo(FoodNationality::class);
    }

    /**
     * Alias for foodNationality relationship (for backward compatibility).
     */
    public function food_nationality(): BelongsTo
    {
        return $this->foodNationality();
    }

    /**
     * Get the sub category that owns the product.
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(InternalCategory::class, 'sub_category_id');
    }

    /**
     * Alias for subCategory relationship (for backward compatibility).
     */
    public function sub_category(): BelongsTo
    {
        return $this->subCategory();
    }

    /**
     * Get the product images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the primary product image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get the option groups for this product.
     */
    public function optionGroups(): HasMany
    {
        return $this->hasMany(OptionGroup::class);
    }

    /**
     * Get the active option groups for this product.
     */
    public function activeOptionGroups(): HasMany
    {
        return $this->hasMany(OptionGroup::class)->where('is_active', true);
    }

    /**
     * Alias for optionGroups relationship (for backward compatibility).
     */
    public function option_groups(): HasMany
    {
        return $this->optionGroups();
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
        $firstImage = $this->images()->first();
        if ($firstImage && $firstImage->image_url) {
            return $firstImage->image_url;
        }
        return '/images/placeholder-product.jpg';
    }

    /**
     * Get all product image URLs.
     */
    public function getImageUrlsAttribute(): array
    {
        return $this->images()->get()->map(function ($image) {
            return $image->image_url;
        })->toArray();
    }

    /**
     * Get the effective price (discounted price if available, otherwise base price).
     */
    public function getEffectivePriceAttribute(): float
    {
        if ($this->discount_percentage && $this->discount_percentage > 0) {
            return $this->base_price - ($this->base_price * $this->discount_percentage / 100);
        }
        return $this->base_price;
    }

    /**
     * Check if product has discount.
     */
    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_percentage !== null && $this->discount_percentage > 0;
    }

    /**
     * Calculate and update discounted price.
     */
    public function calculateDiscountedPrice(): void
    {
        if ($this->discount_percentage && $this->discount_percentage > 0) {
            $discountAmount = ($this->base_price * $this->discount_percentage) / 100;
            $this->discounted_price = $this->base_price - $discountAmount;
        } else {
            $this->discounted_price = null;
        }
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->primaryImage;
        return $primaryImage ? $primaryImage->image_url : null;
    }

    /**
     * Get all image URLs.
     */
    public function getAllImageUrlsAttribute(): array
    {
        return $this->images()->ordered()->get()->map(function ($image) {
            return $image->image_url;
        })->toArray();
    }

    /**
     * Get background URL or color.
     */
    public function getBackgroundUrlAttribute(): ?string
    {
        if ($this->background_type === 'image' && $this->background_value) {
            return ImageHelper::getUrl($this->background_value);
        }
        return $this->background_value; // Return color hex or null
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

    /**
     * Get the price attribute (alias for base_price).
     */
    public function getPriceAttribute(): float
    {
        return $this->base_price;
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrl(): string
    {
        $firstImage = $this->images()->first();
        if ($firstImage && $firstImage->image_url) {
            return $firstImage->image_url;
        }

        // Return default food image
        return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=400&h=300&fit=crop';
    }

    /**
     * Get all image URLs.
     */
    public function getImageUrls(): array
    {
        $images = $this->images()->get();
        if ($images->count() === 0) {
            return [$this->getPrimaryImageUrl()];
        }

        return $images->map(function ($image) {
            return $image->image_url;
        })->toArray();
    }

    /**
     * Get related products.
     */
    public function relatedProducts()
    {
        return $this->hasMany(Product::class, 'internal_category_id', 'internal_category_id')
            ->where('id', '!=', $this->id)
            ->where('is_available', true)
            ->limit(6);
    }

    /**
     * Get product options.
     */
    public function getOptionsAttribute()
    {
        // This would typically come from a dedicated options table
        // For now, return mock data
        return [
            [
                'id' => 'size',
                'type' => 'single',
                'name' => ['en' => 'Size', 'ar' => 'الحجم'],
                'required' => true,
                'choices' => [
                    [
                        'id' => 'small',
                        'name' => ['en' => 'Small', 'ar' => 'صغير'],
                        'price' => 0,
                        'is_default' => true,
                    ],
                    [
                        'id' => 'medium',
                        'name' => ['en' => 'Medium', 'ar' => 'متوسط'],
                        'price' => 5,
                        'is_default' => false,
                    ],
                    [
                        'id' => 'large',
                        'name' => ['en' => 'Large', 'ar' => 'كبير'],
                        'price' => 10,
                        'is_default' => false,
                    ],
                ],
            ],
            [
                'id' => 'extras',
                'type' => 'multiple',
                'name' => ['en' => 'Extras', 'ar' => 'إضافات'],
                'required' => false,
                'max_selections' => 3,
                'choices' => [
                    [
                        'id' => 'cheese',
                        'name' => ['en' => 'Extra Cheese', 'ar' => 'جبن إضافي'],
                        'price' => 3,
                        'is_default' => false,
                    ],
                    [
                        'id' => 'mushrooms',
                        'name' => ['en' => 'Mushrooms', 'ar' => 'فطر'],
                        'price' => 2,
                        'is_default' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * Get product code.
     */
    public function getProductCodeAttribute(): string
    {
        return $this->sku ?? 'PRD-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        // This would typically come from a reviews table
        return 4.5;
    }

    /**
     * Get reviews count.
     */
    public function getReviewsCountAttribute(): int
    {
        // This would typically come from a reviews table
        return rand(10, 100);
    }

    /**
     * Get total orders count.
     */
    public function getTotalOrdersAttribute(): int
    {
        // This would typically come from order_items table
        return rand(50, 500);
    }

    /**
     * Check if product is popular.
     */
    public function getIsPopularAttribute(): bool
    {
        return $this->total_orders > 100;
    }

    /**
     * Check if product is bestseller.
     */
    public function getIsBestsellerAttribute(): bool
    {
        return $this->total_orders > 200;
    }

    /**
     * Check if product is chef special.
     */
    public function getIsChefSpecialAttribute(): bool
    {
        return $this->is_featured && $this->average_rating >= 4.5;
    }


}
