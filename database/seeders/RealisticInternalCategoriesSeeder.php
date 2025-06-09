<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InternalCategory;
use App\Models\Merchant;

class RealisticInternalCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get merchants
        $restaurantMerchant = Merchant::where('business_type', 'restaurant')->first();
        $cafeMerchant = Merchant::where('business_type', 'cafe')->first();

        if (!$restaurantMerchant || !$cafeMerchant) {
            $this->command->warn('Merchants not found. Please run RealisticMerchantsSeeder first.');
            return;
        }

        // Create categories for restaurant
        $this->createRestaurantCategories($restaurantMerchant->id);

        // Create categories for cafe
        $this->createCafeCategories($cafeMerchant->id);

        $this->command->info('Realistic internal categories created successfully!');
    }

    /**
     * Create categories for restaurant merchant.
     */
    private function createRestaurantCategories(int $merchantId): void
    {
        $categories = [
            [
                'name' => ['en' => 'Cold Appetizers', 'ar' => 'المقبلات الباردة'],
                'description' => ['en' => 'Fresh cold starters and salads', 'ar' => 'مقبلات باردة وسلطات طازجة'],
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Hot Appetizers', 'ar' => 'المقبلات الساخنة'],
                'description' => ['en' => 'Warm appetizers and fried items', 'ar' => 'مقبلات ساخنة ومقليات'],
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Soups', 'ar' => 'الشوربات'],
                'description' => ['en' => 'Traditional and international soups', 'ar' => 'شوربات تقليدية وعالمية'],
                'sort_order' => 3,
            ],
            [
                'name' => ['en' => 'Grilled Meats', 'ar' => 'اللحوم المشوية'],
                'description' => ['en' => 'Charcoal grilled lamb, beef and chicken', 'ar' => 'لحم غنم ولحم بقر ودجاج مشوي على الفحم'],
                'sort_order' => 4,
            ],
            [
                'name' => ['en' => 'Traditional Rice Dishes', 'ar' => 'أطباق الأرز التقليدية'],
                'description' => ['en' => 'Kabsa, Mandi and other rice specialties', 'ar' => 'كبسة ومندي وتخصصات الأرز الأخرى'],
                'sort_order' => 5,
            ],
            [
                'name' => ['en' => 'Seafood', 'ar' => 'المأكولات البحرية'],
                'description' => ['en' => 'Fresh fish and seafood dishes', 'ar' => 'أطباق السمك والمأكولات البحرية الطازجة'],
                'sort_order' => 6,
            ],
            [
                'name' => ['en' => 'Vegetarian Dishes', 'ar' => 'الأطباق النباتية'],
                'description' => ['en' => 'Healthy vegetarian and vegan options', 'ar' => 'خيارات نباتية وصحية'],
                'sort_order' => 7,
            ],
            [
                'name' => ['en' => 'Fresh Juices', 'ar' => 'العصائر الطازجة'],
                'description' => ['en' => 'Freshly squeezed fruit juices', 'ar' => 'عصائر فواكه طازجة معصورة'],
                'sort_order' => 8,
            ],
            [
                'name' => ['en' => 'Hot Beverages', 'ar' => 'المشروبات الساخنة'],
                'description' => ['en' => 'Tea, coffee and traditional drinks', 'ar' => 'شاي وقهوة ومشروبات تقليدية'],
                'sort_order' => 9,
            ],
            [
                'name' => ['en' => 'Traditional Desserts', 'ar' => 'الحلويات التقليدية'],
                'description' => ['en' => 'Middle Eastern sweets and desserts', 'ar' => 'حلويات ومعجنات شرق أوسطية'],
                'sort_order' => 10,
            ]
        ];

        foreach ($categories as $category) {
            InternalCategory::create(array_merge($category, [
                'merchant_id' => $merchantId,
                'is_active' => true,
            ]));
        }
    }

    /**
     * Create categories for cafe merchant.
     */
    private function createCafeCategories(int $merchantId): void
    {
        $categories = [
            [
                'name' => ['en' => 'Espresso Based', 'ar' => 'قهوة الإسبريسو'],
                'description' => ['en' => 'Espresso, cappuccino, latte and more', 'ar' => 'إسبريسو وكابتشينو ولاتيه والمزيد'],
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Filter Coffee', 'ar' => 'القهوة المفلترة'],
                'description' => ['en' => 'Pour over, French press and drip coffee', 'ar' => 'قهوة مصبوبة وفرنش برس وقهوة منقطة'],
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Cold Coffee', 'ar' => 'القهوة الباردة'],
                'description' => ['en' => 'Iced coffee, cold brew and frappés', 'ar' => 'قهوة مثلجة وقهوة باردة وفرابيه'],
                'sort_order' => 3,
            ],
            [
                'name' => ['en' => 'Tea Selection', 'ar' => 'تشكيلة الشاي'],
                'description' => ['en' => 'Premium teas from around the world', 'ar' => 'شاي فاخر من جميع أنحاء العالم'],
                'sort_order' => 4,
            ],
            [
                'name' => ['en' => 'Fresh Pastries', 'ar' => 'المعجنات الطازجة'],
                'description' => ['en' => 'Daily baked croissants, muffins and pastries', 'ar' => 'كرواسان ومافن ومعجنات مخبوزة يومياً'],
                'sort_order' => 5,
            ],
            [
                'name' => ['en' => 'Sandwiches & Wraps', 'ar' => 'الساندويتشات واللفائف'],
                'description' => ['en' => 'Fresh sandwiches and healthy wraps', 'ar' => 'ساندويتشات طازجة ولفائف صحية'],
                'sort_order' => 6,
            ],
            [
                'name' => ['en' => 'Salads & Bowls', 'ar' => 'السلطات والأطباق'],
                'description' => ['en' => 'Healthy salads and grain bowls', 'ar' => 'سلطات صحية وأطباق الحبوب'],
                'sort_order' => 7,
            ],
            [
                'name' => ['en' => 'Sweet Treats', 'ar' => 'الحلويات'],
                'description' => ['en' => 'Cakes, cookies and sweet snacks', 'ar' => 'كيك وكوكيز ووجبات خفيفة حلوة'],
                'sort_order' => 8,
            ],
            [
                'name' => ['en' => 'Fresh Juices & Smoothies', 'ar' => 'العصائر والسموذي'],
                'description' => ['en' => 'Fresh fruit juices and healthy smoothies', 'ar' => 'عصائر فواكه طازجة وسموذي صحي'],
                'sort_order' => 9,
            ],
            [
                'name' => ['en' => 'Specialty Drinks', 'ar' => 'المشروبات المميزة'],
                'description' => ['en' => 'Signature drinks and seasonal specials', 'ar' => 'مشروبات مميزة وعروض موسمية'],
                'sort_order' => 10,
            ]
        ];

        foreach ($categories as $category) {
            InternalCategory::create(array_merge($category, [
                'merchant_id' => $merchantId,
                'is_active' => true,
            ]));
        }
    }
}
