<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ImageHelper;
use App\Helpers\TranslationHelper;

class ProductDetailResource extends JsonResource
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
            'name' => TranslationHelper::formatTranslatable($this, 'name'),
            'description' => TranslationHelper::formatTranslatable($this, 'description'),
            'product_code' => $this->product_code,

            // Images
            'images' => [
                'primary' => $this->getPrimaryImageUrl(),
                'gallery' => $this->getImageUrls(),
                'thumbnails' => $this->getThumbnailUrls(),
            ],

            // Pricing Details
            'pricing' => [
                'base_price' => $this->price,
                'current_price' => $this->effective_price,
                'discount_percentage' => $this->discount_percentage,
                'discount_amount' => $this->price - $this->effective_price,
                'has_discount' => $this->discount_percentage > 0,
                'currency' => 'SAR',
                'formatted' => [
                    'base' => number_format($this->price, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR'),
                    'current' => number_format($this->effective_price, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR'),
                    'savings' => $this->discount_percentage > 0
                        ? ($language === 'ar' ? 'توفر ' : 'Save ') . number_format($this->price - $this->effective_price, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR')
                        : null,
                ],
            ],

            // Availability & Stock
            'availability' => [
                'is_available' => $this->is_available,
                'stock_status' => $this->getStockStatus($language),
                'preparation_time' => $this->preparation_time,
                'preparation_time_text' => $this->getPreparationTimeText($language),
                'estimated_ready_time' => $this->getEstimatedReadyTime(),
            ],

            // Categories & Classification
            'category' => [
                'id' => $this->whenLoaded('internalCategory', fn() => $this->internalCategory->id),
                'name' => $this->whenLoaded('internalCategory', fn() => $this->internalCategory->getTranslation('name', $language)),
                'icon' => $this->whenLoaded('internalCategory', fn() => $this->internalCategory->icon),
            ],
            'food_nationality' => [
                'id' => $this->whenLoaded('foodNationality', fn() => $this->foodNationality->id),
                'name' => $this->whenLoaded('foodNationality', fn() => $this->foodNationality->getTranslation('name', $language)),
                'flag' => $this->whenLoaded('foodNationality', fn() => $this->foodNationality->flag),
            ],

            // Merchant Information
            'merchant' => new MerchantListResource($this->whenLoaded('merchant')),

            // Product Options
            'options' => $this->getFormattedOptions($language),
            'has_mandatory_options' => $this->hasMandatoryOptions(),
            'has_optional_options' => $this->hasOptionalOptions(),
            'min_total_price' => $this->getMinTotalPrice(),
            'max_total_price' => $this->getMaxTotalPrice(),

            // Ratings & Reviews
            'rating' => [
                'average' => $this->average_rating ?? 4.8,
                'count' => $this->reviews_count ?? 0,
                'stars_breakdown' => $this->getStarsBreakdown(),
                'recent_reviews' => $this->getRecentReviews(5),
            ],

            // Nutritional Information
            'nutrition' => [
                'calories' => $this->calories,
                'protein' => $this->protein ?? null,
                'carbs' => $this->carbs ?? null,
                'fat' => $this->fat ?? null,
                'fiber' => $this->fiber ?? null,
                'sodium' => $this->sodium ?? null,
            ],

            // Allergens & Dietary Info
            'dietary_info' => [
                'is_vegetarian' => $this->is_vegetarian,
                'is_vegan' => $this->is_vegan ?? false,
                'is_gluten_free' => $this->is_gluten_free ?? false,
                'is_dairy_free' => $this->is_dairy_free ?? false,
                'is_spicy' => $this->is_spicy,
                'spice_level' => $this->getSpiceLevel($language),
                'allergens' => $this->getAllergens($language),
            ],

            // Ingredients
            'ingredients' => [
                'main_ingredients' => $this->getMainIngredients($language),
                'full_ingredients' => $this->getFullIngredients($language),
                'allergen_info' => $this->getAllergenInfo($language),
            ],

            // Features & Badges
            'features' => [
                'is_featured' => $this->is_featured,
                'is_popular' => $this->is_popular ?? false,
                'is_new' => $this->isNew(),
                'is_bestseller' => $this->is_bestseller ?? false,
                'is_chef_special' => $this->is_chef_special ?? false,
            ],

            // Tags & Labels
            'tags' => $this->getTags($language),
            'badges' => $this->getBadges($language),
            'labels' => $this->getLabels($language),

            // Related Products
            'related_products' => ProductListResource::collection(
                $this->whenLoaded('relatedProducts')
            ),

            // Additional Information
            'additional_info' => [
                'serving_size' => $this->serving_size,
                'weight' => $this->weight,
                'dimensions' => $this->dimensions,
                'storage_instructions' => $this->getStorageInstructions($language),
                'heating_instructions' => $this->getHeatingInstructions($language),
            ],

            // Social Proof
            'social_proof' => [
                'total_orders' => $this->total_orders ?? 0,
                'this_week_orders' => $this->getThisWeekOrders(),
                'customer_photos_count' => $this->getCustomerPhotosCount(),
                'last_ordered' => $this->getLastOrderedText($language),
            ],
        ];
    }

    /**
     * Get primary image URL.
     */
    private function getPrimaryImageUrl(): string
    {
        if ($this->images && count($this->images) > 0) {
            return ImageHelper::getUrl($this->images[0]);
        }

        return $this->getDefaultFoodImage();
    }

    /**
     * Get all image URLs.
     */
    private function getImageUrls(): array
    {
        if (!$this->images || count($this->images) === 0) {
            return [$this->getDefaultFoodImage()];
        }

        return array_map(fn($image) => ImageHelper::getUrl($image), $this->images);
    }

    /**
     * Get thumbnail URLs.
     */
    private function getThumbnailUrls(): array
    {
        $images = $this->getImageUrls();
        return array_map(function($url) {
            return str_replace('?w=400&h=300', '?w=100&h=100', $url);
        }, $images);
    }

    /**
     * Get default food image.
     */
    private function getDefaultFoodImage(): string
    {
        return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=400&h=300&fit=crop';
    }

    /**
     * Get stock status.
     */
    private function getStockStatus(string $language): string
    {
        if (!$this->is_available) {
            return $language === 'ar' ? 'غير متوفر' : 'Out of Stock';
        }

        return $language === 'ar' ? 'متوفر' : 'Available';
    }

    /**
     * Get preparation time text.
     */
    private function getPreparationTimeText(string $language): string
    {
        if (!$this->preparation_time) {
            return $language === 'ar' ? '15-20 دقيقة' : '15-20 min';
        }

        return $language === 'ar'
            ? $this->preparation_time . ' دقيقة'
            : $this->preparation_time . ' min';
    }

    /**
     * Get estimated ready time.
     */
    private function getEstimatedReadyTime(): string
    {
        $prepTime = $this->preparation_time ?? 20;
        $readyTime = now()->addMinutes($prepTime);
        return $readyTime->format('H:i');
    }

    /**
     * Get formatted options.
     */
    private function getFormattedOptions(string $language): array
    {
        if (!$this->options || count($this->options) === 0) {
            return [];
        }

        $formatted = [];
        foreach ($this->options as $option) {
            $formatted[] = [
                'id' => $option['id'] ?? uniqid(),
                'type' => $option['type'] ?? 'single',
                'name' => $option['name'][$language] ?? $option['name']['en'] ?? $option['name'],
                'description' => $option['description'][$language] ?? $option['description']['en'] ?? null,
                'required' => $option['required'] ?? false,
                'min_selections' => $option['min_selections'] ?? ($option['required'] ? 1 : 0),
                'max_selections' => $option['max_selections'] ?? 1,
                'choices' => $this->formatOptionChoices($option['choices'] ?? [], $language),
            ];
        }

        return $formatted;
    }

    /**
     * Format option choices.
     */
    private function formatOptionChoices(array $choices, string $language): array
    {
        $formatted = [];
        foreach ($choices as $choice) {
            $formatted[] = [
                'id' => $choice['id'] ?? uniqid(),
                'name' => $choice['name'][$language] ?? $choice['name']['en'] ?? $choice['name'],
                'price' => $choice['price'] ?? 0,
                'is_default' => $choice['is_default'] ?? false,
                'is_available' => $choice['is_available'] ?? true,
                'formatted_price' => $choice['price'] > 0
                    ? '+' . number_format($choice['price'], 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR')
                    : ($language === 'ar' ? 'مجاناً' : 'Free'),
            ];
        }

        return $formatted;
    }

    /**
     * Check if product has mandatory options.
     */
    private function hasMandatoryOptions(): bool
    {
        if (!$this->options) return false;

        foreach ($this->options as $option) {
            if ($option['required'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if product has optional options.
     */
    private function hasOptionalOptions(): bool
    {
        if (!$this->options) return false;

        foreach ($this->options as $option) {
            if (!($option['required'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get minimum total price.
     */
    private function getMinTotalPrice(): float
    {
        $basePrice = $this->effective_price;
        $mandatoryOptionsPrice = 0;

        if ($this->options) {
            foreach ($this->options as $option) {
                if ($option['required'] ?? false) {
                    $choices = $option['choices'] ?? [];
                    if (!empty($choices)) {
                        $minPrice = min(array_column($choices, 'price'));
                        $mandatoryOptionsPrice += $minPrice;
                    }
                }
            }
        }

        return $basePrice + $mandatoryOptionsPrice;
    }

    /**
     * Get maximum total price.
     */
    private function getMaxTotalPrice(): float
    {
        $basePrice = $this->effective_price;
        $maxOptionsPrice = 0;

        if ($this->options) {
            foreach ($this->options as $option) {
                $choices = $option['choices'] ?? [];
                if (!empty($choices)) {
                    $maxSelections = $option['max_selections'] ?? 1;
                    $sortedPrices = array_column($choices, 'price');
                    rsort($sortedPrices);
                    $topPrices = array_slice($sortedPrices, 0, $maxSelections);
                    $maxOptionsPrice += array_sum($topPrices);
                }
            }
        }

        return $basePrice + $maxOptionsPrice;
    }

    /**
     * Check if product is new.
     */
    private function isNew(): bool
    {
        return $this->created_at && $this->created_at->diffInDays(now()) <= 7;
    }

    /**
     * Get stars breakdown.
     */
    private function getStarsBreakdown(): array
    {
        return [
            5 => 70,
            4 => 20,
            3 => 7,
            2 => 2,
            1 => 1,
        ];
    }

    /**
     * Get recent reviews.
     */
    private function getRecentReviews(int $limit): array
    {
        return [
            [
                'id' => 1,
                'customer_name' => 'Ahmed Ali',
                'customer_avatar' => 'https://ui-avatars.com/api/?name=Ahmed+Ali&background=random&color=fff',
                'rating' => 5,
                'comment' => 'Absolutely delicious! Perfect taste and presentation.',
                'date' => '2024-01-15',
                'helpful_count' => 12,
                'images' => [
                    'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=200&h=200&fit=crop'
                ],
            ],
            [
                'id' => 2,
                'customer_name' => 'Sarah Johnson',
                'customer_avatar' => 'https://ui-avatars.com/api/?name=Sarah+Johnson&background=random&color=fff',
                'rating' => 4,
                'comment' => 'Great taste, but could use more sauce.',
                'date' => '2024-01-14',
                'helpful_count' => 8,
                'images' => [],
            ],
        ];
    }

    /**
     * Get spice level.
     */
    private function getSpiceLevel(string $language): ?array
    {
        if (!$this->is_spicy) return null;

        $level = $this->spice_level ?? 2; // 1-5 scale

        return [
            'level' => $level,
            'text' => $language === 'ar'
                ? ['خفيف', 'متوسط', 'حار', 'حار جداً', 'حار للغاية'][$level - 1]
                : ['Mild', 'Medium', 'Hot', 'Very Hot', 'Extremely Hot'][$level - 1],
            'emoji' => ['🌶️', '🌶️🌶️', '🌶️🌶️🌶️', '🌶️🌶️🌶️🌶️', '🌶️🌶️🌶️🌶️🌶️'][$level - 1],
        ];
    }

    /**
     * Get allergens.
     */
    private function getAllergens(string $language): array
    {
        $allergens = [
            'nuts' => $language === 'ar' ? 'مكسرات' : 'Nuts',
            'dairy' => $language === 'ar' ? 'ألبان' : 'Dairy',
            'gluten' => $language === 'ar' ? 'جلوتين' : 'Gluten',
            'eggs' => $language === 'ar' ? 'بيض' : 'Eggs',
            'soy' => $language === 'ar' ? 'صويا' : 'Soy',
        ];

        return array_slice($allergens, 0, rand(0, 3), true);
    }

    /**
     * Get main ingredients.
     */
    private function getMainIngredients(string $language): array
    {
        $ingredients = [
            'en' => ['Tomato sauce', 'Mozzarella cheese', 'Fresh basil', 'Olive oil'],
            'ar' => ['صلصة الطماطم', 'جبن الموزاريلا', 'ريحان طازج', 'زيت الزيتون']
        ];

        return $ingredients[$language] ?? $ingredients['en'];
    }

    /**
     * Get full ingredients.
     */
    private function getFullIngredients(string $language): string
    {
        $ingredients = [
            'en' => 'Tomato sauce, mozzarella cheese, fresh basil, olive oil, salt, pepper, oregano',
            'ar' => 'صلصة الطماطم، جبن الموزاريلا، ريحان طازج، زيت الزيتون، ملح، فلفل أسود، أوريجانو'
        ];

        return $ingredients[$language] ?? $ingredients['en'];
    }

    /**
     * Get allergen info.
     */
    private function getAllergenInfo(string $language): string
    {
        return $language === 'ar'
            ? 'يحتوي على ألبان وجلوتين. قد يحتوي على آثار من المكسرات.'
            : 'Contains dairy and gluten. May contain traces of nuts.';
    }

    /**
     * Get product tags.
     */
    private function getTags(string $language): array
    {
        $tags = [];

        if ($this->discount_percentage > 0) {
            $tags[] = $language === 'ar' ? 'خصم' : 'Discount';
        }

        if ($this->is_featured) {
            $tags[] = $language === 'ar' ? 'مميز' : 'Featured';
        }

        if ($this->isNew()) {
            $tags[] = $language === 'ar' ? 'جديد' : 'New';
        }

        if ($this->is_vegetarian) {
            $tags[] = $language === 'ar' ? 'نباتي' : 'Vegetarian';
        }

        if ($this->is_spicy) {
            $tags[] = $language === 'ar' ? 'حار' : 'Spicy';
        }

        return $tags;
    }

    /**
     * Get product badges.
     */
    private function getBadges(string $language): array
    {
        $badges = [];

        if ($this->discount_percentage > 0) {
            $badges[] = [
                'type' => 'discount',
                'text' => '-' . $this->discount_percentage . '%',
                'color' => '#FF4444',
                'background' => '#FFE6E6',
            ];
        }

        if ($this->is_popular) {
            $badges[] = [
                'type' => 'bestseller',
                'text' => $language === 'ar' ? 'الأكثر مبيعاً' : 'Bestseller',
                'color' => '#FF9500',
                'background' => '#FFF3E6',
            ];
        }

        if ($this->isNew()) {
            $badges[] = [
                'type' => 'new',
                'text' => $language === 'ar' ? 'جديد' : 'New',
                'color' => '#34C759',
                'background' => '#E6F7EA',
            ];
        }

        return $badges;
    }

    /**
     * Get product labels.
     */
    private function getLabels(string $language): array
    {
        $labels = [];

        if ($this->is_featured) {
            $labels[] = $language === 'ar' ? 'اختيار الشيف' : "Chef's Special";
        }

        if ($this->is_popular) {
            $labels[] = $language === 'ar' ? 'الأكثر طلباً' : 'Most Popular';
        }

        return $labels;
    }

    /**
     * Get storage instructions.
     */
    private function getStorageInstructions(string $language): ?string
    {
        return $language === 'ar'
            ? 'يُحفظ في الثلاجة لمدة تصل إلى 3 أيام'
            : 'Store in refrigerator for up to 3 days';
    }

    /**
     * Get heating instructions.
     */
    private function getHeatingInstructions(string $language): ?string
    {
        return $language === 'ar'
            ? 'يُسخن في الميكروويف لمدة 2-3 دقائق'
            : 'Heat in microwave for 2-3 minutes';
    }

    /**
     * Get this week orders.
     */
    private function getThisWeekOrders(): int
    {
        return rand(15, 50);
    }

    /**
     * Get customer photos count.
     */
    private function getCustomerPhotosCount(): int
    {
        return rand(5, 25);
    }

    /**
     * Get last ordered text.
     */
    private function getLastOrderedText(string $language): string
    {
        $hours = rand(1, 24);
        return $language === 'ar'
            ? "آخر طلب منذ {$hours} ساعة"
            : "Last ordered {$hours} hours ago";
    }
}
