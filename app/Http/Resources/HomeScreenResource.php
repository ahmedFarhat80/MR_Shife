<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeScreenResource extends JsonResource
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
            // Hero Section
            'hero' => [
                'banner' => [
                    'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&h=400&fit=crop',
                    'title' => $language === 'ar' ? 'اكتشف أفضل المطاعم' : 'Discover the Best Restaurants',
                    'subtitle' => $language === 'ar' ? 'طعام لذيذ يصل إليك في دقائق' : 'Delicious food delivered in minutes',
                    'action_text' => $language === 'ar' ? 'اطلب الآن' : 'Order Now',
                ],
                'search' => [
                    'placeholder' => $language === 'ar' ? 'ابحث عن مطعم أو طبق' : 'Search for restaurant or dish',
                    'popular_searches' => $this->getPopularSearches($language),
                ],
            ],

            // Quick Categories
            'quick_categories' => [
                'title' => $language === 'ar' ? 'الفئات' : 'Categories',
                'items' => $this->getQuickCategories($language),
            ],

            // Featured Merchants
            'featured_merchants' => [
                'title' => $language === 'ar' ? 'مطاعم مميزة' : 'Featured Restaurants',
                'subtitle' => $language === 'ar' ? 'أفضل المطاعم في منطقتك' : 'Top restaurants in your area',
                'merchants' => MerchantListResource::collection($this->featured_merchants ?? collect()),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Popular Dishes
            'popular_dishes' => [
                'title' => $language === 'ar' ? 'الأطباق الشائعة' : 'Popular Dishes',
                'subtitle' => $language === 'ar' ? 'الأكثر طلباً هذا الأسبوع' : 'Most ordered this week',
                'products' => ProductListResource::collection($this->popular_products ?? collect()),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Nearby Restaurants
            'nearby_restaurants' => [
                'title' => $language === 'ar' ? 'مطاعم قريبة' : 'Nearby Restaurants',
                'subtitle' => $language === 'ar' ? 'توصيل سريع في منطقتك' : 'Fast delivery in your area',
                'merchants' => MerchantListResource::collection($this->nearby_merchants ?? collect()),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Special Offers
            'special_offers' => [
                'title' => $language === 'ar' ? 'عروض خاصة' : 'Special Offers',
                'subtitle' => $language === 'ar' ? 'خصومات وعروض محدودة' : 'Limited time discounts and deals',
                'offers' => $this->getSpecialOffers($language),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Cuisines
            'cuisines' => [
                'title' => $language === 'ar' ? 'المأكولات' : 'Cuisines',
                'subtitle' => $language === 'ar' ? 'اكتشف نكهات من حول العالم' : 'Discover flavors from around the world',
                'items' => $this->getCuisines($language),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Trending Now
            'trending_now' => [
                'title' => $language === 'ar' ? 'الأكثر رواجاً' : 'Trending Now',
                'subtitle' => $language === 'ar' ? 'ما يطلبه الناس الآن' : 'What people are ordering now',
                'products' => ProductListResource::collection($this->trending_products ?? collect()),
                'view_all_text' => $language === 'ar' ? 'عرض الكل' : 'View All',
            ],

            // Quick Actions
            'quick_actions' => [
                'title' => $language === 'ar' ? 'إجراءات سريعة' : 'Quick Actions',
                'actions' => $this->getQuickActions($language),
            ],

            // App Features
            'app_features' => [
                'title' => $language === 'ar' ? 'مميزات التطبيق' : 'App Features',
                'features' => $this->getAppFeatures($language),
            ],

            // User Personalization
            'personalization' => [
                'recommended_for_you' => [
                    'title' => $language === 'ar' ? 'مقترح لك' : 'Recommended for You',
                    'products' => ProductListResource::collection($this->recommended_products ?? collect()),
                ],
                'your_favorites' => [
                    'title' => $language === 'ar' ? 'مفضلاتك' : 'Your Favorites',
                    'merchants' => MerchantListResource::collection($this->favorite_merchants ?? collect()),
                ],
                'order_again' => [
                    'title' => $language === 'ar' ? 'اطلب مرة أخرى' : 'Order Again',
                    'products' => ProductListResource::collection($this->previous_orders ?? collect()),
                ],
            ],

            // Location Info
            'location_info' => [
                'current_location' => [
                    'address' => $this->user_address ?? ($language === 'ar' ? 'الرياض، السعودية' : 'Riyadh, Saudi Arabia'),
                    'is_accurate' => true,
                    'change_text' => $language === 'ar' ? 'تغيير الموقع' : 'Change Location',
                ],
                'delivery_info' => [
                    'estimated_time' => $language === 'ar' ? '25-35 دقيقة' : '25-35 min',
                    'delivery_fee' => $language === 'ar' ? 'رسوم التوصيل تبدأ من 5 ر.س' : 'Delivery fee starts from 5 SAR',
                ],
            ],

            // Promotional Banners
            'promotional_banners' => $this->getPromotionalBanners($language),

            // Footer Info
            'footer_info' => [
                'app_version' => '1.0.0',
                'support_text' => $language === 'ar' ? 'تحتاج مساعدة؟' : 'Need help?',
                'contact_support' => $language === 'ar' ? 'اتصل بالدعم' : 'Contact Support',
            ],
        ];
    }

    /**
     * Get popular searches.
     */
    private function getPopularSearches(string $language): array
    {
        return $language === 'ar' 
            ? ['برجر', 'بيتزا', 'شاورما', 'سوشي', 'سلطة']
            : ['Burger', 'Pizza', 'Shawarma', 'Sushi', 'Salad'];
    }

    /**
     * Get quick categories.
     */
    private function getQuickCategories(string $language): array
    {
        return [
            [
                'id' => 1,
                'name' => $language === 'ar' ? 'وجبات' : 'Meals',
                'icon' => '🍽️',
                'color' => '#FF6B6B',
                'image' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=100&h=100&fit=crop',
            ],
            [
                'id' => 2,
                'name' => $language === 'ar' ? 'حلويات' : 'Desserts',
                'icon' => '🍰',
                'color' => '#4ECDC4',
                'image' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=100&h=100&fit=crop',
            ],
            [
                'id' => 3,
                'name' => $language === 'ar' ? 'مشروبات' : 'Drinks',
                'icon' => '🥤',
                'color' => '#45B7D1',
                'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=100&h=100&fit=crop',
            ],
            [
                'id' => 4,
                'name' => $language === 'ar' ? 'مخبوزات' : 'Pastries',
                'icon' => '🥐',
                'color' => '#F7DC6F',
                'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=100&h=100&fit=crop',
            ],
        ];
    }

    /**
     * Get special offers.
     */
    private function getSpecialOffers(string $language): array
    {
        return [
            [
                'id' => 1,
                'title' => $language === 'ar' ? 'خصم 30%' : '30% Off',
                'description' => $language === 'ar' ? 'على جميع البيتزا' : 'On all pizzas',
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=300&h=200&fit=crop',
                'discount_percentage' => 30,
                'valid_until' => $language === 'ar' ? 'صالح حتى نهاية الأسبوع' : 'Valid until end of week',
                'merchant_name' => $language === 'ar' ? 'مطعم الشيف الماهر' : 'Master Chef Restaurant',
                'action_text' => $language === 'ar' ? 'اطلب الآن' : 'Order Now',
            ],
            [
                'id' => 2,
                'title' => $language === 'ar' ? 'توصيل مجاني' : 'Free Delivery',
                'description' => $language === 'ar' ? 'على الطلبات فوق 50 ر.س' : 'On orders above 50 SAR',
                'image' => 'https://images.unsplash.com/photo-1571091718767-18b5b1457add?w=300&h=200&fit=crop',
                'discount_type' => 'free_delivery',
                'minimum_order' => 50,
                'valid_until' => $language === 'ar' ? 'صالح اليوم فقط' : 'Valid today only',
                'action_text' => $language === 'ar' ? 'اطلب الآن' : 'Order Now',
            ],
        ];
    }

    /**
     * Get cuisines.
     */
    private function getCuisines(string $language): array
    {
        return [
            [
                'id' => 1,
                'name' => $language === 'ar' ? 'إيطالي' : 'Italian',
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=200&h=150&fit=crop',
                'restaurants_count' => 25,
                'flag' => '🇮🇹',
            ],
            [
                'id' => 2,
                'name' => $language === 'ar' ? 'أمريكي' : 'American',
                'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=150&fit=crop',
                'restaurants_count' => 18,
                'flag' => '🇺🇸',
            ],
            [
                'id' => 3,
                'name' => $language === 'ar' ? 'عربي' : 'Arabic',
                'image' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=200&h=150&fit=crop',
                'restaurants_count' => 32,
                'flag' => '🇸🇦',
            ],
            [
                'id' => 4,
                'name' => $language === 'ar' ? 'آسيوي' : 'Asian',
                'image' => 'https://images.unsplash.com/photo-1563379091339-03246963d51a?w=200&h=150&fit=crop',
                'restaurants_count' => 15,
                'flag' => '🇯🇵',
            ],
        ];
    }

    /**
     * Get quick actions.
     */
    private function getQuickActions(string $language): array
    {
        return [
            [
                'id' => 'reorder',
                'title' => $language === 'ar' ? 'إعادة الطلب' : 'Reorder',
                'icon' => '🔄',
                'color' => '#FF6B6B',
                'action' => 'reorder_last',
            ],
            [
                'id' => 'favorites',
                'title' => $language === 'ar' ? 'المفضلة' : 'Favorites',
                'icon' => '❤️',
                'color' => '#4ECDC4',
                'action' => 'show_favorites',
            ],
            [
                'id' => 'offers',
                'title' => $language === 'ar' ? 'العروض' : 'Offers',
                'icon' => '🎁',
                'color' => '#45B7D1',
                'action' => 'show_offers',
            ],
            [
                'id' => 'track',
                'title' => $language === 'ar' ? 'تتبع الطلب' : 'Track Order',
                'icon' => '📍',
                'color' => '#F7DC6F',
                'action' => 'track_order',
            ],
        ];
    }

    /**
     * Get app features.
     */
    private function getAppFeatures(string $language): array
    {
        return [
            [
                'title' => $language === 'ar' ? 'توصيل سريع' : 'Fast Delivery',
                'description' => $language === 'ar' ? 'توصيل في 30 دقيقة أو أقل' : 'Delivery in 30 minutes or less',
                'icon' => '🚚',
            ],
            [
                'title' => $language === 'ar' ? 'دفع آمن' : 'Secure Payment',
                'description' => $language === 'ar' ? 'طرق دفع متعددة وآمنة' : 'Multiple secure payment methods',
                'icon' => '💳',
            ],
            [
                'title' => $language === 'ar' ? 'تتبع مباشر' : 'Live Tracking',
                'description' => $language === 'ar' ? 'تتبع طلبك في الوقت الفعلي' : 'Track your order in real-time',
                'icon' => '📱',
            ],
        ];
    }

    /**
     * Get promotional banners.
     */
    private function getPromotionalBanners(string $language): array
    {
        return [
            [
                'id' => 1,
                'title' => $language === 'ar' ? 'اشترك في الباقة الشهرية' : 'Subscribe to Monthly Plan',
                'subtitle' => $language === 'ar' ? 'توصيل مجاني لمدة شهر كامل' : 'Free delivery for a full month',
                'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400&h=200&fit=crop',
                'action_text' => $language === 'ar' ? 'اشترك الآن' : 'Subscribe Now',
                'background_color' => '#FF6B6B',
            ],
            [
                'id' => 2,
                'title' => $language === 'ar' ? 'ادع صديقاً واحصل على خصم' : 'Refer a Friend and Get Discount',
                'subtitle' => $language === 'ar' ? 'خصم 20% لك ولصديقك' : '20% off for you and your friend',
                'image' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=400&h=200&fit=crop',
                'action_text' => $language === 'ar' ? 'ادع الآن' : 'Refer Now',
                'background_color' => '#4ECDC4',
            ],
        ];
    }
}
