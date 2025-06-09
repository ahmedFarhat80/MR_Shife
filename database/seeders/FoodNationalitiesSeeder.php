<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FoodNationality;

class FoodNationalitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foodNationalities = [
            [
                'name' => [
                    'en' => 'Middle Eastern & Arabic',
                    'ar' => 'أكل شرقي وعربي'
                ],
                'description' => [
                    'en' => 'Traditional Middle Eastern and Arabic cuisine',
                    'ar' => 'المأكولات الشرقية والعربية التقليدية'
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Fast Food',
                    'ar' => 'وجبات سريعة'
                ],
                'description' => [
                    'en' => 'Quick service meals and snacks',
                    'ar' => 'وجبات سريعة ووجبات خفيفة'
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Eastern Sweets',
                    'ar' => 'حلويات شرقية'
                ],
                'description' => [
                    'en' => 'Traditional Middle Eastern desserts and sweets',
                    'ar' => 'الحلويات والمعجنات الشرقية التقليدية'
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Italian Cuisine',
                    'ar' => 'مأكولات إيطالية'
                ],
                'description' => [
                    'en' => 'Authentic Italian dishes and pasta',
                    'ar' => 'الأطباق الإيطالية الأصيلة والمعكرونة'
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Asian Cuisine',
                    'ar' => 'مأكولات آسيوية'
                ],
                'description' => [
                    'en' => 'Asian dishes including Chinese, Japanese, and Thai',
                    'ar' => 'الأطباق الآسيوية بما في ذلك الصينية واليابانية والتايلاندية'
                ],
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Beverages',
                    'ar' => 'مشروبات'
                ],
                'description' => [
                    'en' => 'Hot and cold beverages, juices, and drinks',
                    'ar' => 'المشروبات الساخنة والباردة والعصائر'
                ],
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Appetizers',
                    'ar' => 'مقبلات'
                ],
                'description' => [
                    'en' => 'Starters and appetizers',
                    'ar' => 'المقبلات والمشهيات'
                ],
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Main Dishes',
                    'ar' => 'أطباق رئيسية'
                ],
                'description' => [
                    'en' => 'Main course meals and dishes',
                    'ar' => 'الأطباق الرئيسية والوجبات الأساسية'
                ],
                'sort_order' => 8,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Seafood',
                    'ar' => 'مأكولات بحرية'
                ],
                'description' => [
                    'en' => 'Fresh seafood and fish dishes',
                    'ar' => 'المأكولات البحرية الطازجة وأطباق السمك'
                ],
                'sort_order' => 9,
                'is_active' => true,
            ],
            [
                'name' => [
                    'en' => 'Grilled & BBQ',
                    'ar' => 'مشاوي وباربكيو'
                ],
                'description' => [
                    'en' => 'Grilled meats and barbecue specialties',
                    'ar' => 'اللحوم المشوية وتخصصات الباربكيو'
                ],
                'sort_order' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($foodNationalities as $nationality) {
            FoodNationality::create($nationality);
        }
    }
}
