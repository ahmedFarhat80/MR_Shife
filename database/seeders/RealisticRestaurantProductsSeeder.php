<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\OptionGroup;
use App\Models\Option;
use App\Models\InternalCategory;
use App\Models\FoodNationality;
use App\Models\Merchant;

class RealisticRestaurantProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get restaurant merchant
        $merchant = Merchant::where('business_type', 'restaurant')->first();
        if (!$merchant) {
            $this->command->warn('Restaurant merchant not found.');
            return;
        }

        // Get categories and nationalities
        $categories = InternalCategory::where('merchant_id', $merchant->id)->get();
        $nationalities = FoodNationality::all();

        if ($categories->isEmpty() || $nationalities->isEmpty()) {
            $this->command->warn('Categories or nationalities not found.');
            return;
        }

        // Create products for each category
        $this->createColdAppetizers($merchant->id, $categories, $nationalities);
        $this->createHotAppetizers($merchant->id, $categories, $nationalities);
        $this->createGrilledMeats($merchant->id, $categories, $nationalities);
        $this->createRiceDishes($merchant->id, $categories, $nationalities);
        $this->createBeverages($merchant->id, $categories, $nationalities);
        $this->createDesserts($merchant->id, $categories, $nationalities);

        $this->command->info('Restaurant products created successfully!');
    }

    /**
     * Create cold appetizers.
     */
    private function createColdAppetizers(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Cold Appetizers')->first();
        $nationality = $nationalities->where('name->en', 'Middle Eastern & Arabic')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Mixed Arabic Appetizers', 'ar' => 'مقبلات عربية مشكلة'],
                'description' => ['en' => 'A selection of traditional Arabic cold appetizers including hummus, tabbouleh, fattoush, and baba ganoush', 'ar' => 'تشكيلة من المقبلات العربية الباردة التقليدية تشمل الحمص والتبولة والفتوش وبابا غنوج'],
                'base_price' => 35.00,
                'discount_percentage' => 10.00,
                'background_type' => 'color',
                'background_value' => '#8FBC8F',
                'preparation_time' => 8,
                'is_featured' => true,
                'is_vegetarian' => true,
                'calories' => 320,
                'ingredients' => ['chickpeas', 'tahini', 'olive oil', 'parsley', 'tomatoes', 'cucumber', 'eggplant'],
                'allergens' => ['sesame'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Portion Size', 'ar' => 'حجم الحصة'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (2-3 people)', 'ar' => 'صغير (2-3 أشخاص)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium (4-5 people)', 'ar' => 'متوسط (4-5 أشخاص)'], 'price_modifier' => 15.00],
                            ['name' => ['en' => 'Large (6-8 people)', 'ar' => 'كبير (6-8 أشخاص)'], 'price_modifier' => 25.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Extra Items', 'ar' => 'إضافات'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Extra Bread', 'ar' => 'خبز إضافي'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Pickles & Olives', 'ar' => 'مخللات وزيتون'], 'price_modifier' => 8.00],
                            ['name' => ['en' => 'Cheese Platter', 'ar' => 'طبق الجبن'], 'price_modifier' => 12.00],
                        ]
                    ]
                ]
            ],
            [
                'name' => ['en' => 'Fresh Fattoush Salad', 'ar' => 'سلطة الفتوش الطازجة'],
                'description' => ['en' => 'Traditional Lebanese salad with mixed greens, tomatoes, cucumbers, and crispy bread with sumac dressing', 'ar' => 'سلطة لبنانية تقليدية بالخضار المشكلة والطماطم والخيار والخبز المحمص مع تتبيلة السماق'],
                'base_price' => 18.00,
                'background_type' => 'color',
                'background_value' => '#90EE90',
                'preparation_time' => 5,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'calories' => 180,
                'ingredients' => ['lettuce', 'tomatoes', 'cucumber', 'radish', 'parsley', 'mint', 'sumac', 'olive oil'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Regular', 'ar' => 'عادي'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Large', 'ar' => 'كبير'], 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Add Protein', 'ar' => 'إضافة بروتين'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Grilled Chicken', 'ar' => 'دجاج مشوي'], 'price_modifier' => 12.00],
                            ['name' => ['en' => 'Halloumi Cheese', 'ar' => 'جبنة الحلوم'], 'price_modifier' => 8.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create hot appetizers.
     */
    private function createHotAppetizers(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Hot Appetizers')->first();
        $nationality = $nationalities->where('name->en', 'Middle Eastern & Arabic')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Crispy Kibbeh', 'ar' => 'كبة مقلية'],
                'description' => ['en' => 'Traditional fried kibbeh filled with spiced meat and pine nuts', 'ar' => 'كبة مقلية تقليدية محشوة باللحم المتبل والصنوبر'],
                'base_price' => 22.00,
                'background_type' => 'color',
                'background_value' => '#D2691E',
                'preparation_time' => 12,
                'calories' => 280,
                'ingredients' => ['bulgur', 'ground meat', 'onions', 'pine nuts', 'spices'],
                'allergens' => ['gluten', 'nuts'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Quantity', 'ar' => 'الكمية'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => '6 pieces', 'ar' => '6 قطع'], 'price_modifier' => 0.00],
                            ['name' => ['en' => '12 pieces', 'ar' => '12 قطعة'], 'price_modifier' => 18.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Sauce', 'ar' => 'الصوص'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Yogurt Sauce', 'ar' => 'صوص اللبن'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Tahini Sauce', 'ar' => 'صوص الطحينة'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Spicy Sauce', 'ar' => 'صوص حار'], 'price_modifier' => 0.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create grilled meats.
     */
    private function createGrilledMeats(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Grilled Meats')->first();
        $nationality = $nationalities->where('name->en', 'Grilled & BBQ')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Mixed Grill Platter', 'ar' => 'طبق المشاوي المشكلة'],
                'description' => ['en' => 'A generous platter of grilled lamb chops, chicken tikka, and beef kebab served with rice and grilled vegetables', 'ar' => 'طبق سخي من ريش الغنم المشوية وتكة الدجاج وكباب اللحم يُقدم مع الأرز والخضار المشوية'],
                'base_price' => 85.00,
                'discount_percentage' => 15.00,
                'background_type' => 'color',
                'background_value' => '#8B4513',
                'preparation_time' => 25,
                'is_featured' => true,
                'calories' => 650,
                'ingredients' => ['lamb', 'chicken', 'beef', 'rice', 'vegetables', 'spices'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Meat Selection', 'ar' => 'اختيار اللحم'],
                        'type' => 'customization',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Extra Lamb Chops', 'ar' => 'ريش غنم إضافية'], 'price_modifier' => 25.00],
                            ['name' => ['en' => 'Extra Chicken', 'ar' => 'دجاج إضافي'], 'price_modifier' => 15.00],
                            ['name' => ['en' => 'Extra Beef Kebab', 'ar' => 'كباب لحم إضافي'], 'price_modifier' => 20.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Side Dishes', 'ar' => 'الأطباق الجانبية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Grilled Tomatoes', 'ar' => 'طماطم مشوية'], 'price_modifier' => 8.00],
                            ['name' => ['en' => 'French Fries', 'ar' => 'بطاطس مقلية'], 'price_modifier' => 12.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create rice dishes.
     */
    private function createRiceDishes(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Traditional Rice Dishes')->first();
        $nationality = $nationalities->where('name->en', 'Middle Eastern & Arabic')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Chicken Kabsa', 'ar' => 'كبسة الدجاج'],
                'description' => ['en' => 'Traditional Saudi rice dish with tender chicken, aromatic spices, and almonds', 'ar' => 'طبق الأرز السعودي التقليدي مع الدجاج الطري والبهارات العطرة واللوز'],
                'base_price' => 45.00,
                'background_type' => 'color',
                'background_value' => '#DAA520',
                'preparation_time' => 35,
                'is_featured' => true,
                'calories' => 580,
                'ingredients' => ['basmati rice', 'chicken', 'tomatoes', 'onions', 'kabsa spices', 'almonds'],
                'allergens' => ['nuts'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Portion Size', 'ar' => 'حجم الحصة'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Individual', 'ar' => 'فردي'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Family (4-6 people)', 'ar' => 'عائلي (4-6 أشخاص)'], 'price_modifier' => 80.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Spice Level', 'ar' => 'مستوى التوابل'],
                        'type' => 'customization',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Mild', 'ar' => 'خفيف'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium', 'ar' => 'متوسط'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Spicy', 'ar' => 'حار'], 'price_modifier' => 0.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create beverages.
     */
    private function createBeverages(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Fresh Juices')->first();
        $nationality = $nationalities->where('name->en', 'Beverages')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Fresh Orange Juice', 'ar' => 'عصير البرتقال الطازج'],
                'description' => ['en' => 'Freshly squeezed orange juice with no added sugar', 'ar' => 'عصير برتقال طازج معصور بدون سكر مضاف'],
                'base_price' => 15.00,
                'background_type' => 'color',
                'background_value' => '#FFA500',
                'preparation_time' => 3,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'calories' => 120,
                'ingredients' => ['fresh oranges'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (250ml)', 'ar' => 'صغير (250 مل)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Large (500ml)', 'ar' => 'كبير (500 مل)'], 'price_modifier' => 8.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create desserts.
     */
    private function createDesserts(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Traditional Desserts')->first();
        $nationality = $nationalities->where('name->en', 'Eastern Sweets')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Kunafa with Cheese', 'ar' => 'كنافة بالجبن'],
                'description' => ['en' => 'Traditional Middle Eastern dessert with crispy shredded pastry, sweet cheese, and sugar syrup', 'ar' => 'حلوى شرق أوسطية تقليدية بالعجين المبشور المقرمش والجبن الحلو والقطر'],
                'base_price' => 25.00,
                'background_type' => 'color',
                'background_value' => '#F4A460',
                'preparation_time' => 15,
                'is_vegetarian' => true,
                'calories' => 380,
                'ingredients' => ['kunafa dough', 'cheese', 'sugar syrup', 'pistachios'],
                'allergens' => ['gluten', 'dairy', 'nuts'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Topping', 'ar' => 'الإضافة العلوية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Extra Pistachios', 'ar' => 'فستق إضافي'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Ice Cream Scoop', 'ar' => 'كرة آيس كريم'], 'price_modifier' => 8.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $category->id, $nationality->id, $productData);
        }
    }

    /**
     * Create a product with its options.
     */
    private function createProductWithOptions(int $merchantId, int $categoryId, int $nationalityId, array $productData): void
    {
        // Extract options data
        $optionsData = $productData['options'] ?? [];
        unset($productData['options']);

        // Calculate discounted price
        $discountedPrice = null;
        if (isset($productData['discount_percentage']) && $productData['discount_percentage'] > 0) {
            $discountAmount = ($productData['base_price'] * $productData['discount_percentage']) / 100;
            $discountedPrice = $productData['base_price'] - $discountAmount;
        }

        // Create the product
        $product = Product::create(array_merge($productData, [
            'merchant_id' => $merchantId,
            'internal_category_id' => $categoryId,
            'food_nationality_id' => $nationalityId,
            'discounted_price' => $discountedPrice,
            'is_available' => true,
            'sku' => 'REST-' . strtoupper(substr(md5(uniqid()), 0, 8)),
        ]));

        // Create sample product images
        $this->createSampleImages($product->id);

        // Create option groups and options
        foreach ($optionsData as $groupIndex => $groupData) {
            $optionGroup = OptionGroup::create([
                'product_id' => $product->id,
                'name' => $groupData['group_name'],
                'type' => $groupData['type'],
                'is_required' => $groupData['is_required'],
                'min_selections' => $groupData['min_selections'],
                'max_selections' => $groupData['max_selections'],
                'sort_order' => $groupIndex + 1,
                'is_active' => true,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                Option::create([
                    'option_group_id' => $optionGroup->id,
                    'name' => $optionData['name'],
                    'price_modifier' => $optionData['price_modifier'],
                    'is_available' => true,
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }
    }

    /**
     * Create sample images for a product.
     */
    private function createSampleImages(int $productId): void
    {
        $images = [
            [
                'image_path' => 'product_images/restaurant_' . $productId . '_main.jpg',
                'alt_text' => ['en' => 'Product main image', 'ar' => 'الصورة الرئيسية للمنتج'],
                'is_primary' => true,
                'sort_order' => 1,
            ],
            [
                'image_path' => 'product_images/restaurant_' . $productId . '_detail.jpg',
                'alt_text' => ['en' => 'Product detail view', 'ar' => 'عرض تفاصيل المنتج'],
                'is_primary' => false,
                'sort_order' => 2,
            ],
        ];

        foreach ($images as $imageData) {
            ProductImage::create(array_merge($imageData, [
                'product_id' => $productId,
            ]));
        }
    }
}
