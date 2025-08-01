<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ImageHelper;
use App\Helpers\TranslationHelper;

class MerchantDetailResource extends JsonResource
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
            'business_name' => TranslationHelper::formatTranslatable($this, 'business_name'),
            'description' => TranslationHelper::formatTranslatable($this, 'business_description'),
            'business_type' => $this->business_type,

            // Images
            'images' => [
                'logo' => $this->business_logo ? ImageHelper::getUrl($this->business_logo) : $this->getDefaultLogo(),
                'cover' => $this->getCoverImageUrl(),
                'gallery' => $this->getGalleryImages(),
            ],

            // Location & Delivery Info
            'location' => [
                'address' => TranslationHelper::formatTranslatable($this, 'location_address'),
                'city' => $this->location_city,
                'area' => $this->location_area,
                'postal_code' => $this->location_postal_code,
                'coordinates' => [
                    'latitude' => $this->location_latitude,
                    'longitude' => $this->location_longitude,
                ],
            ],

            'delivery' => [
                'radius' => $this->delivery_radius,
                'fee' => $this->delivery_fee,
                'minimum_order' => $this->minimum_order,
                'estimated_time' => $this->getEstimatedDeliveryTime(),
                'is_available' => $this->isDeliveryAvailable(),
            ],

            // Business Hours
            'business_hours' => $this->formatBusinessHours($language),
            'is_open_now' => $this->isOpenNow(),
            'opening_status' => $this->getOpeningStatus($language),

            // Ratings & Reviews
            'rating' => [
                'average' => $this->average_rating ?? 4.5,
                'count' => $this->reviews_count ?? 0,
                'stars_breakdown' => $this->getStarsBreakdown(),
                'recent_reviews' => $this->getRecentReviews(3),
            ],

            // Contact Information
            'contact' => [
                'phone' => $this->business_phone,
                'email' => $this->business_email,
            ],

            // Categories
            'categories' => InternalCategoryResource::collection(
                $this->whenLoaded('internalCategories')
            ),

            // Featured Products
            'featured_products' => ProductListResource::collection(
                $this->whenLoaded('featuredProducts')
            ),

            // Popular Products
            'popular_products' => ProductListResource::collection(
                $this->whenLoaded('popularProducts')
            ),

            // Offers & Discounts
            'offers' => [
                'has_active_offers' => $this->hasActiveOffers(),
                'discount_products_count' => $this->getDiscountProductsCount(),
                'max_discount' => $this->getMaxDiscountPercentage(),
                'special_offers' => $this->getSpecialOffers(),
            ],

            // Statistics
            'stats' => [
                'total_products' => $this->products_count ?? $this->products()->count(),
                'categories_count' => $this->internal_categories_count ?? $this->internalCategories()->count(),
                'orders_count' => $this->orders_count ?? 0,
                'years_in_business' => $this->getYearsInBusiness(),
            ],

            // Features & Badges
            'features' => [
                'is_verified' => $this->is_verified,
                'is_featured' => $this->is_featured ?? false,
                'accepts_online_payment' => true,
                'has_loyalty_program' => false,
                'eco_friendly' => false,
            ],

            // Additional Info
            'cuisine_types' => [],
            'tags' => [],
            'distance' => $this->when(
                $request->has(['user_lat', 'user_lng']),
                fn() => $this->calculateDistance(
                    $request->user_lat,
                    $request->user_lng
                )
            ),
        ];
    }

    /**
     * Get default logo.
     */
    private function getDefaultLogo(): string
    {
        $name = $this->getTranslation('business_name', 'en');
        $initials = $this->getInitials($name);
        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=200";
    }

    /**
     * Get cover image URL.
     */
    private function getCoverImageUrl(): string
    {
        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=400&fit=crop';
    }

    /**
     * Get gallery images.
     */
    private function getGalleryImages(): array
    {
        return [
            'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=400&h=300&fit=crop',
            'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=400&h=300&fit=crop',
            'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300&fit=crop',
        ];
    }

    /**
     * Format business hours for display.
     */
    private function formatBusinessHours(string $language): array
    {
        $days = [
            'saturday' => $language === 'ar' ? 'السبت' : 'Saturday',
            'sunday' => $language === 'ar' ? 'الأحد' : 'Sunday',
            'monday' => $language === 'ar' ? 'الاثنين' : 'Monday',
            'tuesday' => $language === 'ar' ? 'الثلاثاء' : 'Tuesday',
            'wednesday' => $language === 'ar' ? 'الأربعاء' : 'Wednesday',
            'thursday' => $language === 'ar' ? 'الخميس' : 'Thursday',
            'friday' => $language === 'ar' ? 'الجمعة' : 'Friday',
        ];

        $businessHours = $this->business_hours ?? [];
        $formatted = [];

        foreach ($days as $key => $dayName) {
            $hours = $businessHours[$key] ?? null;
            $formatted[] = [
                'day' => $dayName,
                'is_open' => $hours !== null && isset($hours['open']) && isset($hours['close']),
                'open_time' => $hours['open'] ?? null,
                'close_time' => $hours['close'] ?? null,
                'formatted' => ($hours && isset($hours['open']) && isset($hours['close']))
                    ? "{$hours['open']} - {$hours['close']}"
                    : ($language === 'ar' ? 'مغلق' : 'Closed'),
            ];
        }

        return $formatted;
    }

    /**
     * Get opening status.
     */
    private function getOpeningStatus(string $language): array
    {
        $isOpen = $this->isOpenNow();

        return [
            'is_open' => $isOpen,
            'status_text' => $isOpen
                ? ($language === 'ar' ? 'مفتوح الآن' : 'Open Now')
                : ($language === 'ar' ? 'مغلق الآن' : 'Closed Now'),
            'next_opening' => $isOpen ? null : $this->getNextOpeningTime($language),
        ];
    }

    /**
     * Check if merchant is currently open.
     */
    private function isOpenNow(): bool
    {
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $businessHours = $this->business_hours ?? [];
        $hours = $businessHours[$dayOfWeek] ?? null;

        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Check if delivery is available.
     */
    private function isDeliveryAvailable(): bool
    {
        return $this->isOpenNow() && $this->status === 'active';
    }

    /**
     * Get estimated delivery time.
     */
    private function getEstimatedDeliveryTime(): string
    {
        return '25-35 min';
    }

    /**
     * Get stars breakdown.
     */
    private function getStarsBreakdown(): array
    {
        return [
            5 => 65,
            4 => 20,
            3 => 10,
            2 => 3,
            1 => 2,
        ];
    }

    /**
     * Get recent reviews.
     */
    private function getRecentReviews(int $limit): array
    {
        // This would typically come from a reviews table
        return [
            [
                'id' => 1,
                'customer_name' => 'Ahmed Ali',
                'rating' => 5,
                'comment' => 'Excellent food and service!',
                'date' => '2024-01-15',
            ],
            [
                'id' => 2,
                'customer_name' => 'Sarah Johnson',
                'rating' => 4,
                'comment' => 'Great atmosphere and delicious meals.',
                'date' => '2024-01-14',
            ],
        ];
    }

    /**
     * Check if merchant has active offers.
     */
    private function hasActiveOffers(): bool
    {
        return $this->products()
            ->whereNotNull('discount_percentage')
            ->where('discount_percentage', '>', 0)
            ->exists();
    }

    /**
     * Get discount products count.
     */
    private function getDiscountProductsCount(): int
    {
        return $this->products()
            ->whereNotNull('discount_percentage')
            ->where('discount_percentage', '>', 0)
            ->count();
    }

    /**
     * Get maximum discount percentage.
     */
    private function getMaxDiscountPercentage(): ?float
    {
        return $this->products()
            ->whereNotNull('discount_percentage')
            ->max('discount_percentage');
    }

    /**
     * Get special offers.
     */
    private function getSpecialOffers(): array
    {
        return [
            [
                'title' => 'Free Delivery',
                'description' => 'On orders above 50 SAR',
                'type' => 'delivery',
            ],
            [
                'title' => '20% Off',
                'description' => 'On selected items',
                'type' => 'discount',
            ],
        ];
    }

    /**
     * Get years in business.
     */
    private function getYearsInBusiness(): int
    {
        return $this->created_at ? $this->created_at->diffInYears(now()) : 0;
    }

    /**
     * Get cuisine types.
     */
    private function getCuisineTypes(string $language): array
    {
        try {
            return $this->products()
                ->with('foodNationality')
                ->get()
                ->pluck('foodNationality')
                ->filter()
                ->unique('id')
                ->map(function ($nationality) use ($language) {
                    if (!$nationality) {
                        return null;
                    }
                    return [
                        'id' => $nationality->id,
                        'name' => $nationality->getTranslation('name', $language),
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            // Return empty array if there's an error
            return [];
        }
    }

    /**
     * Get tags.
     */
    private function getTags(string $language): array
    {
        $tags = [];

        if ($this->hasActiveOffers()) {
            $tags[] = $language === 'ar' ? 'عروض خاصة' : 'Special Offers';
        }

        if ($this->is_featured) {
            $tags[] = $language === 'ar' ? 'مميز' : 'Featured';
        }

        if ($this->delivery_fee == 0) {
            $tags[] = $language === 'ar' ? 'توصيل مجاني' : 'Free Delivery';
        }

        return $tags;
    }

    /**
     * Get initials from name.
     */
    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials ?: 'MR';
    }

    /**
     * Calculate distance.
     */
    private function calculateDistance(float $userLat, float $userLng): array
    {
        $distance = $this->haversineDistance(
            $userLat,
            $userLng,
            $this->location_latitude,
            $this->location_longitude
        );

        return [
            'value' => round($distance, 1),
            'unit' => 'km',
            'text' => round($distance, 1) . ' km',
        ];
    }

    /**
     * Calculate distance using Haversine formula.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get next opening time.
     */
    private function getNextOpeningTime(string $language): ?string
    {
        // Simplified implementation
        return $language === 'ar' ? 'يفتح غداً في 9:00 ص' : 'Opens tomorrow at 9:00 AM';
    }
}
