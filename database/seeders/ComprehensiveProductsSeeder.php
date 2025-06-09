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

class ComprehensiveProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first merchant for testing
        $merchant = Merchant::first();
        if (!$merchant) {
            $this->command->warn('No merchants found. Please run MerchantsSeeder first.');
            return;
        }

        // Get categories and nationalities
        $categories = InternalCategory::where('merchant_id', $merchant->id)->get();
        $nationalities = FoodNationality::all();

        if ($categories->isEmpty() || $nationalities->isEmpty()) {
            $this->command->warn('No categories or nationalities found. Please run seeders first.');
            return;
        }

        // Create comprehensive product samples
        $this->createBeverageProducts($merchant->id, $categories, $nationalities);
        $this->createMainDishProducts($merchant->id, $categories, $nationalities);
        $this->createDessertProducts($merchant->id, $categories, $nationalities);
        $this->createAppetizerProducts($merchant->id, $categories, $nationalities);

        $this->command->info('Comprehensive products created successfully!');
    }

    /**
     * Create beverage products with customization options.
     */
    private function createBeverageProducts(int $merchantId, $categories, $nationalities): void
    {
        $beverageCategory = $categories->where('name->en', 'Beverages')->first();
        $beverageNationality = $nationalities->where('name->en', 'Beverages')->first();

        if (!$beverageCategory || !$beverageNationality) return;

        $beverages = [
            [
                'name' => ['en' => 'Fresh Orange Juice', 'ar' => 'عصير البرتقال الطازج'],
                'description' => ['en' => 'Freshly squeezed orange juice', 'ar' => 'عصير برتقال طازج معصور'],
                'base_price' => 12.00,
                'background_type' => 'color',
                'background_value' => '#FF8C00',
                'preparation_time' => 5,
                'is_featured' => true,
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (250ml)', 'ar' => 'صغير (250 مل)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium (350ml)', 'ar' => 'متوسط (350 مل)'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Large (500ml)', 'ar' => 'كبير (500 مل)'], 'price_modifier' => 6.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Add-ons', 'ar' => 'الإضافات'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Extra Ice', 'ar' => 'ثلج إضافي'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Mint Leaves', 'ar' => 'أوراق النعناع'], 'price_modifier' => 1.00],
                            ['name' => ['en' => 'Ginger', 'ar' => 'زنجبيل'], 'price_modifier' => 1.50],
                        ]
                    ]
                ]
            ],
            [
                'name' => ['en' => 'Turkish Coffee', 'ar' => 'القهوة التركية'],
                'description' => ['en' => 'Traditional Turkish coffee', 'ar' => 'قهوة تركية تقليدية'],
                'base_price' => 8.00,
                'background_type' => 'color',
                'background_value' => '#8B4513',
                'preparation_time' => 8,
                'options' => [
                    [
                        'group_name' => ['en' => 'Sugar Level', 'ar' => 'مستوى السكر'],
                        'type' => 'customization',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'No Sugar', 'ar' => 'بدون سكر'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Little Sugar', 'ar' => 'سكر قليل'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium Sugar', 'ar' => 'سكر متوسط'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Sweet', 'ar' => 'حلو'], 'price_modifier' => 0.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($beverages as $productData) {
            $this->createProductWithOptions($merchantId, $beverageCategory->id, $beverageNationality->id, $productData);
        }
    }

    /**
     * Create main dish products.
     */
    private function createMainDishProducts(int $merchantId, $categories, $nationalities): void
    {
        $mainCategory = $categories->where('name->en', 'Main Dishes')->first();
        $arabicNationality = $nationalities->where('name->en', 'Middle Eastern & Arabic')->first();

        if (!$mainCategory || !$arabicNationality) return;

        $mainDishes = [
            [
                'name' => ['en' => 'Chicken Shawarma', 'ar' => 'شاورما الدجاج'],
                'description' => ['en' => 'Grilled chicken with vegetables and sauce', 'ar' => 'دجاج مشوي مع الخضار والصوص'],
                'base_price' => 18.00,
                'discount_percentage' => 15.00,
                'background_type' => 'color',
                'background_value' => '#DAA520',
                'preparation_time' => 12,
                'is_featured' => true,
                'options' => [
                    [
                        'group_name' => ['en' => 'Bread Type', 'ar' => 'نوع الخبز'],
                        'type' => 'ingredient',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Pita Bread', 'ar' => 'خبز البيتا'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Saj Bread', 'ar' => 'خبز الصاج'], 'price_modifier' => 1.00],
                            ['name' => ['en' => 'French Bread', 'ar' => 'الخبز الفرنسي'], 'price_modifier' => 2.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Sauce', 'ar' => 'الصوص'],
                        'type' => 'ingredient',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Garlic Sauce', 'ar' => 'صوص الثوم'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Tahini', 'ar' => 'الطحينة'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Hot Sauce', 'ar' => 'الصوص الحار'], 'price_modifier' => 0.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($mainDishes as $productData) {
            $this->createProductWithOptions($merchantId, $mainCategory->id, $arabicNationality->id, $productData);
        }
    }

    /**
     * Create dessert products.
     */
    private function createDessertProducts(int $merchantId, $categories, $nationalities): void
    {
        $dessertCategory = $categories->where('name->en', 'Desserts')->first();
        $sweetNationality = $nationalities->where('name->en', 'Eastern Sweets')->first();

        if (!$dessertCategory || !$sweetNationality) return;

        $desserts = [
            [
                'name' => ['en' => 'Baklava', 'ar' => 'البقلاوة'],
                'description' => ['en' => 'Traditional Middle Eastern pastry with nuts', 'ar' => 'معجنات شرقية تقليدية بالمكسرات'],
                'base_price' => 15.00,
                'background_type' => 'color',
                'background_value' => '#F4A460',
                'preparation_time' => 5,
                'options' => [
                    [
                        'group_name' => ['en' => 'Nuts Type', 'ar' => 'نوع المكسرات'],
                        'type' => 'ingredient',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Pistachios', 'ar' => 'الفستق'], 'price_modifier' => 2.00],
                            ['name' => ['en' => 'Walnuts', 'ar' => 'الجوز'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Mixed Nuts', 'ar' => 'مكسرات مشكلة'], 'price_modifier' => 1.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($desserts as $productData) {
            $this->createProductWithOptions($merchantId, $dessertCategory->id, $sweetNationality->id, $productData);
        }
    }

    /**
     * Create appetizer products.
     */
    private function createAppetizerProducts(int $merchantId, $categories, $nationalities): void
    {
        $appetizerCategory = $categories->where('name->en', 'Appetizers & Starters')->first();
        $arabicNationality = $nationalities->where('name->en', 'Middle Eastern & Arabic')->first();

        if (!$appetizerCategory || !$arabicNationality) return;

        $appetizers = [
            [
                'name' => ['en' => 'Hummus Plate', 'ar' => 'طبق الحمص'],
                'description' => ['en' => 'Creamy hummus with olive oil and spices', 'ar' => 'حمص كريمي بزيت الزيتون والبهارات'],
                'base_price' => 10.00,
                'background_type' => 'color',
                'background_value' => '#DEB887',
                'preparation_time' => 3,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'options' => [
                    [
                        'group_name' => ['en' => 'Toppings', 'ar' => 'الإضافات العلوية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Pine Nuts', 'ar' => 'الصنوبر'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Paprika', 'ar' => 'البابريكا'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Whole Chickpeas', 'ar' => 'حبات الحمص'], 'price_modifier' => 1.00],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($appetizers as $productData) {
            $this->createProductWithOptions($merchantId, $appetizerCategory->id, $arabicNationality->id, $productData);
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
            'sku' => 'SKU-' . strtoupper(substr(md5(uniqid()), 0, 8)),
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
                'image_path' => 'product_images/sample_' . $productId . '_1.jpg',
                'alt_text' => ['en' => 'Product main image', 'ar' => 'الصورة الرئيسية للمنتج'],
                'is_primary' => true,
                'sort_order' => 1,
            ],
            [
                'image_path' => 'product_images/sample_' . $productId . '_2.jpg',
                'alt_text' => ['en' => 'Product detail image', 'ar' => 'صورة تفاصيل المنتج'],
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
