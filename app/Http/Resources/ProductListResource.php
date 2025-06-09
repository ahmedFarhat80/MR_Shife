<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ImageHelper;

class ProductListResource extends JsonResource
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
            'short_description' => $this->getShortDescription($language),

            // Images
            'image' => $this->getPrimaryImageUrl(),
            'images' => $this->getImageUrls(),

            // Pricing
            'price' => [
                'original' => $this->base_price ?? 0,
                'current' => $this->effective_price ?? $this->base_price ?? 0,
                'discount_percentage' => $this->discount_percentage ?? 0,
                'has_discount' => ($this->discount_percentage ?? 0) > 0,
                'currency' => 'SAR',
                'formatted' => [
                    'original' => number_format($this->base_price ?? 0, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR'),
                    'current' => number_format($this->effective_price ?? $this->base_price ?? 0, 2) . ' ' . ($language === 'ar' ? 'ر.س' : 'SAR'),
                ],
            ],

            // Availability
            'availability' => [
                'is_available' => $this->is_available,
                'stock_status' => $this->getStockStatus($language),
                'preparation_time' => $this->preparation_time,
                'preparation_time_text' => $this->getPreparationTimeText($language),
            ],

            // Categories & Classification
            'category' => [
                'id' => $this->whenLoaded('internalCategory', fn() => $this->internalCategory->id),
                'name' => $this->whenLoaded('internalCategory', fn() => $this->internalCategory->getTranslation('name', $language)),
            ],
            'food_nationality' => [
                'id' => $this->whenLoaded('foodNationality', fn() => $this->foodNationality->id),
                'name' => $this->whenLoaded('foodNationality', fn() => $this->foodNationality->getTranslation('name', $language)),
            ],

            // Merchant Info
            'merchant' => [
                'id' => $this->whenLoaded('merchant', fn() => $this->merchant->id),
                'name' => $this->whenLoaded('merchant', fn() => $this->merchant->getTranslation('business_name', $language)),
                'logo' => $this->whenLoaded('merchant', fn() => $this->merchant->business_logo
                    ? ImageHelper::getUrl($this->merchant->business_logo)
                    : $this->getDefaultMerchantLogo()),
            ],

            // Features & Tags
            'features' => [
                'is_featured' => $this->is_featured,
                'is_popular' => $this->is_popular ?? false,
                'is_new' => $this->isNew(),
                'is_vegetarian' => $this->is_vegetarian,
                'is_spicy' => $this->is_spicy,
                'has_options' => $this->hasOptions(),
            ],

            // Ratings
            'rating' => [
                'average' => $this->average_rating ?? 4.5,
                'count' => $this->reviews_count ?? 0,
                'stars' => $this->getStarsDisplay(),
            ],

            // Quick Info
            'tags' => $this->getTags($language),
            'badges' => $this->getBadges($language),

            // Options Summary
            'options_summary' => $this->getOptionsSummary($language),

            // Additional Info
            'calories' => $this->calories,
            'allergens' => $this->getAllergens($language),
            'ingredients_summary' => $this->getIngredientsSummary($language),
        ];
    }

    /**
     * Get primary image URL.
     */
    private function getPrimaryImageUrl(): string
    {
        try {
            // Try to get primary image from relationship
            if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
                return $this->primaryImage->image_url;
            }

            // Try to get from images array
            $images = $this->images ?? [];
            if (is_array($images) && count($images) > 0) {
                return ImageHelper::getUrl($images[0]);
            }

            // Use the model's method (different method name to avoid recursion)
            if (method_exists($this, 'getPrimaryImageUrlAttribute')) {
                return $this->getPrimaryImageUrlAttribute();
            }
        } catch (\Exception) {
            // Return default food image based on category
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
     * Get default food image.
     */
    private function getDefaultFoodImage(): string
    {
        $defaultImages = [
            'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=400&h=300&fit=crop', // Pizza
            'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop', // Burger
            'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=400&h=300&fit=crop', // Salad
            'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&h=300&fit=crop', // Food
        ];

        return $defaultImages[array_rand($defaultImages)];
    }

    /**
     * Get short description.
     */
    private function getShortDescription(string $language): string
    {
        $description = $this->getTranslation('description', $language);
        return strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
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
     * Check if product is new.
     */
    private function isNew(): bool
    {
        return $this->created_at && $this->created_at->diffInDays(now()) <= 7;
    }

    /**
     * Check if product has options.
     */
    private function hasOptions(): bool
    {
        return $this->options && count($this->options) > 0;
    }

    /**
     * Get stars display.
     */
    private function getStarsDisplay(): string
    {
        $rating = $this->average_rating ?? 4.5;
        $fullStars = floor($rating);
        $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        return str_repeat('★', $fullStars) .
               str_repeat('☆', $halfStar) .
               str_repeat('☆', $emptyStars);
    }

    /**
     * Get product tags.
     */
    private function getTags(string $language): array
    {
        $tags = [];

        if (($this->discount_percentage ?? 0) > 0) {
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

        if (($this->discount_percentage ?? 0) > 0) {
            $badges[] = [
                'type' => 'discount',
                'text' => '-' . ($this->discount_percentage ?? 0) . '%',
                'color' => '#FF4444',
            ];
        }

        if ($this->is_popular) {
            $badges[] = [
                'type' => 'popular',
                'text' => $language === 'ar' ? 'الأكثر طلباً' : 'Popular',
                'color' => '#FF9500',
            ];
        }

        if ($this->isNew()) {
            $badges[] = [
                'type' => 'new',
                'text' => $language === 'ar' ? 'جديد' : 'New',
                'color' => '#34C759',
            ];
        }

        return $badges;
    }

    /**
     * Get options summary.
     */
    private function getOptionsSummary(string $language): array
    {
        if (!$this->hasOptions()) {
            return [];
        }

        $summary = [];
        $options = $this->options;

        foreach ($options as $option) {
            if (isset($option['type']) && isset($option['name'])) {
                $summary[] = [
                    'type' => $option['type'],
                    'name' => $option['name'][$language] ?? $option['name']['en'] ?? $option['name'],
                    'required' => $option['required'] ?? false,
                    'choices_count' => count($option['choices'] ?? []),
                ];
            }
        }

        return $summary;
    }

    /**
     * Get allergens.
     */
    private function getAllergens(string $language): array
    {
        // This would typically come from a dedicated allergens field
        $allergens = [
            'nuts' => $language === 'ar' ? 'مكسرات' : 'Nuts',
            'dairy' => $language === 'ar' ? 'ألبان' : 'Dairy',
            'gluten' => $language === 'ar' ? 'جلوتين' : 'Gluten',
        ];

        // Return random allergens for demo
        return array_slice($allergens, 0, rand(0, 2));
    }

    /**
     * Get ingredients summary.
     */
    private function getIngredientsSummary(string $language): string
    {
        // This would typically come from an ingredients field
        $ingredients = [
            'en' => 'Tomato sauce, mozzarella cheese, fresh vegetables',
            'ar' => 'صلصة الطماطم، جبن الموزاريلا، خضروات طازجة'
        ];

        return $ingredients[$language] ?? $ingredients['en'];
    }

    /**
     * Get default merchant logo.
     */
    private function getDefaultMerchantLogo(): string
    {
        return "https://ui-avatars.com/api/?name=MR&background=random&color=fff&size=200";
    }
}
