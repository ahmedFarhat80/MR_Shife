<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InternalCategory;

class InternalCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create global categories that all merchants can use
        $this->createGlobalCategories();

        $this->command->info('Global internal categories created successfully!');
    }

    /**
     * Create global internal categories.
     */
    private function createGlobalCategories(): void
    {
        $categories = [
            [
                'name' => ['en' => 'Appetizers & Starters', 'ar' => 'المقبلات والمشهيات'],
                'description' => ['en' => 'Delicious starters to begin your meal', 'ar' => 'مقبلات شهية لبداية وجبتك'],
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Soups & Salads', 'ar' => 'الشوربات والسلطات'],
                'description' => ['en' => 'Fresh soups and healthy salads', 'ar' => 'شوربات طازجة وسلطات صحية'],
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Main Dishes', 'ar' => 'الأطباق الرئيسية'],
                'description' => ['en' => 'Hearty main courses and entrees', 'ar' => 'أطباق رئيسية دسمة ومشبعة'],
                'sort_order' => 3,
            ],
            [
                'name' => ['en' => 'Grilled & BBQ', 'ar' => 'المشاوي والباربكيو'],
                'description' => ['en' => 'Grilled specialties and barbecue', 'ar' => 'تخصصات المشاوي والباربكيو'],
                'sort_order' => 4,
            ],
            [
                'name' => ['en' => 'Pasta & Rice', 'ar' => 'المعكرونة والأرز'],
                'description' => ['en' => 'Pasta dishes and rice specialties', 'ar' => 'أطباق المعكرونة وتخصصات الأرز'],
                'sort_order' => 5,
            ],
            [
                'name' => ['en' => 'Seafood', 'ar' => 'المأكولات البحرية'],
                'description' => ['en' => 'Fresh seafood and fish dishes', 'ar' => 'مأكولات بحرية طازجة وأطباق السمك'],
                'sort_order' => 6,
            ],
            [
                'name' => ['en' => 'Vegetarian', 'ar' => 'الأطباق النباتية'],
                'description' => ['en' => 'Healthy vegetarian options', 'ar' => 'خيارات نباتية صحية'],
                'sort_order' => 7,
            ],
            [
                'name' => ['en' => 'Beverages', 'ar' => 'المشروبات'],
                'description' => ['en' => 'Hot and cold beverages', 'ar' => 'مشروبات ساخنة وباردة'],
                'sort_order' => 8,
            ],
            [
                'name' => ['en' => 'Fresh Juices', 'ar' => 'العصائر الطازجة'],
                'description' => ['en' => 'Freshly squeezed juices', 'ar' => 'عصائر طازجة معصورة'],
                'sort_order' => 9,
            ],
            [
                'name' => ['en' => 'Desserts', 'ar' => 'الحلويات'],
                'description' => ['en' => 'Sweet treats and desserts', 'ar' => 'حلويات ومعجنات حلوة'],
                'sort_order' => 10,
            ],
            [
                'name' => ['en' => 'Kids Menu', 'ar' => 'قائمة الأطفال'],
                'description' => ['en' => 'Special dishes for children', 'ar' => 'أطباق خاصة للأطفال'],
                'sort_order' => 11,
            ],
            [
                'name' => ['en' => 'Breakfast', 'ar' => 'الإفطار'],
                'description' => ['en' => 'Morning breakfast options', 'ar' => 'خيارات إفطار الصباح'],
                'sort_order' => 12,
            ],
        ];

        foreach ($categories as $category) {
            InternalCategory::create(array_merge($category, [
                'is_active' => true,
            ]));
        }
    }
}
