<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\ImageHelper;

class MerchantListResource extends JsonResource
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
            'business_name' => $this->getTranslation('business_name', $language),
            'description' => $this->getTranslation('business_description', $language),
            'business_type' => $this->business_type,
            'logo' => $this->business_logo ? ImageHelper::getUrl($this->business_logo) : $this->getDefaultLogo(),
            'cover_image' => $this->getCoverImageUrl(),

            // Location & Delivery Info
            'location' => [
                'address' => $this->getTranslation('location_address', $language),
                'city' => $this->location_city,
                'area' => $this->location_area,
                'latitude' => $this->location_latitude,
                'longitude' => $this->location_longitude,
            ],
            'delivery' => [
                'radius' => $this->delivery_radius,
                'fee' => $this->delivery_fee,
                'minimum_order' => $this->minimum_order,
                'estimated_time' => $this->getEstimatedDeliveryTime(),
            ],

            // Business Hours
            'business_hours' => $this->business_hours,
            'is_open_now' => $this->isOpenNow(),
            'next_opening_time' => $this->getNextOpeningTime(),

            // Ratings & Reviews
            'rating' => [
                'average' => $this->average_rating ?? 4.5,
                'count' => $this->reviews_count ?? 0,
                'stars' => $this->getStarsBreakdown(),
            ],

            // Status & Features
            'status' => $this->status,
            'is_featured' => $this->is_featured ?? false,
            'is_verified' => $this->is_verified,
            'has_discount' => $this->hasActiveDiscounts(),
            'discount_percentage' => $this->getMaxDiscountPercentage(),

            // Contact Info
            'contact' => [
                'phone' => $this->business_phone,
                'email' => $this->business_email,
            ],

            // Additional Info
            'cuisine_types' => $this->getCuisineTypes(),
            'popular_dishes' => $this->getPopularDishes(3),
            'distance' => $this->when(
                $request->has(['user_lat', 'user_lng']),
                fn() => $this->calculateDistance(
                    $request->user_lat,
                    $request->user_lng
                )
            ),

            // Quick Stats
            'stats' => [
                'total_products' => $this->products_count ?? $this->products()->count(),
                'categories_count' => $this->internal_categories_count ?? $this->internalCategories()->count(),
                'orders_count' => $this->orders_count ?? 0,
            ],
        ];
    }

    /**
     * Get default logo based on business type.
     */
    private function getDefaultLogo(): string
    {
        $businessType = $this->business_type;
        $name = $this->getTranslation('business_name', 'en');
        $initials = $this->getInitials($name);

        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=200";
    }

    /**
     * Get cover image URL.
     */
    private function getCoverImageUrl(): string
    {
        // Return a default cover image or merchant's cover if available
        return 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=400&fit=crop';
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
     * Check if merchant is currently open.
     */
    private function isOpenNow(): bool
    {
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        $currentTime = $now->format('H:i');

        $hours = $this->business_hours[$dayOfWeek] ?? null;

        if (!$hours || !isset($hours['open']) || !isset($hours['close'])) {
            return false;
        }

        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    /**
     * Get next opening time.
     */
    private function getNextOpeningTime(): ?string
    {
        if ($this->isOpenNow()) {
            return null;
        }

        // Logic to find next opening time
        return null; // Simplified for now
    }

    /**
     * Get estimated delivery time.
     */
    private function getEstimatedDeliveryTime(): string
    {
        return '25-35 min'; // This could be calculated based on distance and current load
    }

    /**
     * Get stars breakdown for rating.
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
     * Check if merchant has active discounts.
     */
    private function hasActiveDiscounts(): bool
    {
        return $this->products()
            ->whereNotNull('discount_percentage')
            ->where('discount_percentage', '>', 0)
            ->exists();
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
     * Get cuisine types.
     */
    private function getCuisineTypes(): array
    {
        try {
            return $this->products()
                ->with('foodNationality')
                ->get()
                ->map(function ($product) {
                    if (!$product->foodNationality) {
                        return null;
                    }

                    $name = $product->foodNationality->name;

                    // Handle translatable name (JSON format)
                    if (is_array($name)) {
                        return $name['en'] ?? $name['ar'] ?? null;
                    }

                    return $name;
                })
                ->filter()
                ->unique()
                ->take(3)
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get popular dishes.
     */
    private function getPopularDishes(int $limit = 3): array
    {
        try {
            return $this->products()
                ->where('is_available', true)
                ->where(function($query) {
                    $query->where('is_featured', true)
                          ->orWhere('is_popular', true);
                })
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    $name = $product->name;

                    // Handle translatable name (JSON format)
                    if (is_array($name)) {
                        $displayName = $name['en'] ?? $name['ar'] ?? 'Unknown';
                    } else {
                        $displayName = $name ?? 'Unknown';
                    }

                    return [
                        'id' => $product->id,
                        'name' => $displayName,
                        'image' => $product->primary_image_url ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=300&h=200&fit=crop',
                        'price' => $product->effective_price ?? $product->base_price,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calculate distance between merchant and user.
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
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
