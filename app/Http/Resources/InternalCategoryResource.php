<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ImageHelper;

class InternalCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $language = $request->header('X-Language', 'en');

        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', $language),
            'description' => $this->getTranslation('description', $language),
            'icon' => $this->icon,
            'image' => $this->getImageUrl(),
            'color' => $this->color ?? $this->getRandomColor(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,

            // Products count
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn() => $this->products->count(),
                fn() => $this->products_count ?? 0
            ),

            // Available products count (only active and available products)
            'available_products_count' => $this->when(
                $this->relationLoaded('products'),
                fn() => $this->products->where('is_available', true)->count(),
                fn() => $this->available_products_count ?? 0
            ),

            // Featured products
            'featured_products' => ProductListResource::collection(
                $this->whenLoaded('featuredProducts')
            ),

            // Sample products for preview
            'sample_products' => $this->getSampleProducts($language),

            // Category statistics
            'stats' => [
                'total_products' => $this->products_count ?? 0,
                'available_products' => $this->available_products_count ?? 0,
                'featured_products' => $this->featured_products_count ?? 0,
                'average_price' => $this->getAveragePrice(),
                'price_range' => $this->getPriceRange(),
            ],

            // Category features
            'features' => [
                'has_vegetarian_options' => $this->hasVegetarianOptions(),
                'has_spicy_options' => $this->hasSpicyOptions(),
                'has_discounted_items' => $this->hasDiscountedItems(),
                'preparation_time_range' => $this->getPreparationTimeRange($language),
            ],

            // Merchant info (if category belongs to specific merchant)
            'merchant' => [
                'id' => $this->whenLoaded('merchant', fn() => $this->merchant->id),
                'name' => $this->whenLoaded('merchant', fn() => $this->merchant->getTranslation('business_name', $language)),
            ],
        ];
    }

    /**
     * Get category image URL.
     */
    private function getImageUrl(): string
    {
        if ($this->image) {
            return ImageHelper::getUrl($this->image);
        }

        // Return default category image based on name or icon
        return $this->getDefaultCategoryImage();
    }

    /**
     * Get default category image.
     */
    private function getDefaultCategoryImage(): string
    {
        $defaultImages = [
            'meals' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=300&h=200&fit=crop',
            'desserts' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=300&h=200&fit=crop',
            'drinks' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=300&h=200&fit=crop',
            'pastries' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=300&h=200&fit=crop',
            'pizza' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=300&h=200&fit=crop',
            'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=300&h=200&fit=crop',
            'salad' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=300&h=200&fit=crop',
        ];

        try {
            $name = $this->name ?? [];
            $categoryName = '';

            if (is_array($name)) {
                $categoryName = strtolower($name['en'] ?? $name['ar'] ?? '');
            } else {
                $categoryName = strtolower($name);
            }

            foreach ($defaultImages as $key => $image) {
                if ($categoryName && str_contains($categoryName, $key)) {
                    return $image;
                }
            }
        } catch (\Exception) {
            // If translation fails, continue to default
        }

        // Return random default image
        $imageValues = array_values($defaultImages);
        return $imageValues[array_rand($imageValues)];
    }

    /**
     * Get random color for category.
     */
    private function getRandomColor(): string
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#F7DC6F',
            '#BB8FCE', '#85C1E9', '#F8C471', '#82E0AA',
            '#F1948A', '#85C1E9', '#F4D03F', '#A9DFBF'
        ];

        return $colors[array_rand($colors)];
    }

    /**
     * Get sample products for category preview.
     */
    private function getSampleProducts(string $language): array
    {
        if (!$this->relationLoaded('products')) {
            return [];
        }

        return $this->products
            ->where('is_available', true)
            ->take(3)
            ->map(function ($product) use ($language) {
                try {
                    $effectivePrice = $product->effective_price ?? $product->base_price ?? 0;

                    return [
                        'id' => $product->id,
                        'name' => $product->getTranslation('name', $language) ?? 'Unknown Product',
                        'image' => $product->getPrimaryImageUrl() ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=200&fit=crop',
                        'price' => $effectivePrice,
                        'formatted_price' => number_format($effectivePrice, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR'),
                        'has_discount' => ($product->discount_percentage ?? 0) > 0,
                    ];
                } catch (\Exception) {
                    return null;
                }
            })
            ->filter()
            ->toArray();
    }

    /**
     * Get average price of products in category.
     */
    private function getAveragePrice(): ?float
    {
        if (!$this->relationLoaded('products')) {
            return null;
        }

        $availableProducts = $this->products->where('is_available', true);

        if ($availableProducts->isEmpty()) {
            return null;
        }

        return round($availableProducts->avg('effective_price'), 2);
    }

    /**
     * Get price range of products in category.
     */
    private function getPriceRange(): ?array
    {
        if (!$this->relationLoaded('products')) {
            return null;
        }

        $availableProducts = $this->products->where('is_available', true);

        if ($availableProducts->isEmpty()) {
            return null;
        }

        $prices = $availableProducts->pluck('effective_price');

        return [
            'min' => $prices->min(),
            'max' => $prices->max(),
            'formatted' => [
                'min' => number_format($prices->min(), 2),
                'max' => number_format($prices->max(), 2),
                'range' => number_format($prices->min(), 2) . ' - ' . number_format($prices->max(), 2),
            ],
        ];
    }

    /**
     * Check if category has vegetarian options.
     */
    private function hasVegetarianOptions(): bool
    {
        if (!$this->relationLoaded('products')) {
            return false;
        }

        return $this->products
            ->where('is_available', true)
            ->where('is_vegetarian', true)
            ->isNotEmpty();
    }

    /**
     * Check if category has spicy options.
     */
    private function hasSpicyOptions(): bool
    {
        if (!$this->relationLoaded('products')) {
            return false;
        }

        return $this->products
            ->where('is_available', true)
            ->where('is_spicy', true)
            ->isNotEmpty();
    }

    /**
     * Check if category has discounted items.
     */
    private function hasDiscountedItems(): bool
    {
        if (!$this->relationLoaded('products')) {
            return false;
        }

        return $this->products
            ->where('is_available', true)
            ->where('discount_percentage', '>', 0)
            ->isNotEmpty();
    }

    /**
     * Get preparation time range for category.
     */
    private function getPreparationTimeRange(string $language): ?array
    {
        if (!$this->relationLoaded('products')) {
            return null;
        }

        $availableProducts = $this->products->where('is_available', true);

        if ($availableProducts->isEmpty()) {
            return null;
        }

        $preparationTimes = $availableProducts
            ->whereNotNull('preparation_time')
            ->pluck('preparation_time');

        if ($preparationTimes->isEmpty()) {
            return null;
        }

        $min = $preparationTimes->min();
        $max = $preparationTimes->max();

        return [
            'min' => $min,
            'max' => $max,
            'formatted' => $language === 'ar'
                ? "{$min}-{$max} دقيقة"
                : "{$min}-{$max} min",
        ];
    }
}
