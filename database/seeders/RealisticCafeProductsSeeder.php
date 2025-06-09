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

class RealisticCafeProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get cafe merchant
        $merchant = Merchant::where('business_type', 'cafe')->first();
        if (!$merchant) {
            $this->command->warn('Cafe merchant not found.');
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
        $this->createEspressoBasedDrinks($merchant->id, $categories, $nationalities);
        $this->createColdCoffee($merchant->id, $categories, $nationalities);
        $this->createTeaSelection($merchant->id, $categories, $nationalities);
        $this->createPastries($merchant->id, $categories, $nationalities);
        $this->createSandwiches($merchant->id, $categories, $nationalities);
        $this->createSweetTreats($merchant->id, $categories, $nationalities);

        $this->command->info('Cafe products created successfully!');
    }

    /**
     * Create espresso based drinks.
     */
    private function createEspressoBasedDrinks(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Espresso Based')->first();
        $nationality = $nationalities->where('name->en', 'Beverages')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Signature Cappuccino', 'ar' => 'كابتشينو مميز'],
                'description' => ['en' => 'Rich espresso with steamed milk and velvety foam, topped with cocoa powder', 'ar' => 'إسبريسو غني مع الحليب المبخر والرغوة الناعمة، مع رش الكاكاو'],
                'base_price' => 18.00,
                'discount_percentage' => 10.00,
                'background_type' => 'color',
                'background_value' => '#8B4513',
                'preparation_time' => 4,
                'is_featured' => true,
                'calories' => 150,
                'ingredients' => ['espresso', 'steamed milk', 'milk foam', 'cocoa powder'],
                'allergens' => ['dairy'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (8oz)', 'ar' => 'صغير (8 أونصة)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium (12oz)', 'ar' => 'متوسط (12 أونصة)'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Large (16oz)', 'ar' => 'كبير (16 أونصة)'], 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Milk Type', 'ar' => 'نوع الحليب'],
                        'type' => 'ingredient',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Whole Milk', 'ar' => 'حليب كامل الدسم'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Skim Milk', 'ar' => 'حليب خالي الدسم'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Oat Milk', 'ar' => 'حليب الشوفان'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Almond Milk', 'ar' => 'حليب اللوز'], 'price_modifier' => 3.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Extra Shots', 'ar' => 'جرعات إضافية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Extra Shot', 'ar' => 'جرعة إضافية'], 'price_modifier' => 4.00],
                            ['name' => ['en' => 'Decaf Shot', 'ar' => 'جرعة خالية من الكافيين'], 'price_modifier' => 0.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Sweeteners', 'ar' => 'المحليات'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Sugar', 'ar' => 'سكر'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Honey', 'ar' => 'عسل'], 'price_modifier' => 2.00],
                            ['name' => ['en' => 'Vanilla Syrup', 'ar' => 'شراب الفانيليا'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Caramel Syrup', 'ar' => 'شراب الكراميل'], 'price_modifier' => 3.00],
                        ]
                    ]
                ]
            ],
            [
                'name' => ['en' => 'Caffe Latte', 'ar' => 'لاتيه'],
                'description' => ['en' => 'Smooth espresso with steamed milk and a light layer of foam', 'ar' => 'إسبريسو ناعم مع الحليب المبخر وطبقة خفيفة من الرغوة'],
                'base_price' => 16.00,
                'background_type' => 'color',
                'background_value' => '#D2B48C',
                'preparation_time' => 3,
                'calories' => 190,
                'ingredients' => ['espresso', 'steamed milk', 'milk foam'],
                'allergens' => ['dairy'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (8oz)', 'ar' => 'صغير (8 أونصة)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Medium (12oz)', 'ar' => 'متوسط (12 أونصة)'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Large (16oz)', 'ar' => 'كبير (16 أونصة)'], 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Milk Type', 'ar' => 'نوع الحليب'],
                        'type' => 'ingredient',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Whole Milk', 'ar' => 'حليب كامل الدسم'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Skim Milk', 'ar' => 'حليب خالي الدسم'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Oat Milk', 'ar' => 'حليب الشوفان'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Almond Milk', 'ar' => 'حليب اللوز'], 'price_modifier' => 3.00],
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
     * Create cold coffee drinks.
     */
    private function createColdCoffee(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Cold Coffee')->first();
        $nationality = $nationalities->where('name->en', 'Beverages')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Iced Caramel Macchiato', 'ar' => 'ماكياتو الكراميل المثلج'],
                'description' => ['en' => 'Espresso with vanilla syrup, cold milk, and caramel drizzle over ice', 'ar' => 'إسبريسو مع شراب الفانيليا والحليب البارد وصوص الكراميل على الثلج'],
                'base_price' => 22.00,
                'background_type' => 'color',
                'background_value' => '#DEB887',
                'preparation_time' => 5,
                'is_featured' => true,
                'calories' => 250,
                'ingredients' => ['espresso', 'vanilla syrup', 'cold milk', 'caramel sauce', 'ice'],
                'allergens' => ['dairy'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Medium (16oz)', 'ar' => 'متوسط (16 أونصة)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Large (20oz)', 'ar' => 'كبير (20 أونصة)'], 'price_modifier' => 6.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Ice Level', 'ar' => 'مستوى الثلج'],
                        'type' => 'customization',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Light Ice', 'ar' => 'ثلج خفيف'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Regular Ice', 'ar' => 'ثلج عادي'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Extra Ice', 'ar' => 'ثلج إضافي'], 'price_modifier' => 0.00],
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
     * Create tea selection.
     */
    private function createTeaSelection(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Tea Selection')->first();
        $nationality = $nationalities->where('name->en', 'Beverages')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Earl Grey Tea', 'ar' => 'شاي إيرل جراي'],
                'description' => ['en' => 'Classic black tea with bergamot oil, served hot with lemon and honey', 'ar' => 'شاي أسود كلاسيكي بزيت البرغموت، يُقدم ساخناً مع الليمون والعسل'],
                'base_price' => 12.00,
                'background_type' => 'color',
                'background_value' => '#8B4513',
                'preparation_time' => 4,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'calories' => 5,
                'ingredients' => ['black tea', 'bergamot oil'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Additions', 'ar' => 'الإضافات'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name' => ['en' => 'Lemon Slice', 'ar' => 'شريحة ليمون'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Honey', 'ar' => 'عسل'], 'price_modifier' => 2.00],
                            ['name' => ['en' => 'Milk', 'ar' => 'حليب'], 'price_modifier' => 2.00],
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
     * Create pastries.
     */
    private function createPastries(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Fresh Pastries')->first();
        $nationality = $nationalities->where('name->en', 'Fast Food')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Butter Croissant', 'ar' => 'كرواسان بالزبدة'],
                'description' => ['en' => 'Flaky, buttery croissant baked fresh daily', 'ar' => 'كرواسان مقرمش بالزبدة مخبوز طازج يومياً'],
                'base_price' => 8.00,
                'background_type' => 'color',
                'background_value' => '#F4A460',
                'preparation_time' => 2,
                'is_vegetarian' => true,
                'calories' => 230,
                'ingredients' => ['flour', 'butter', 'yeast', 'milk', 'eggs'],
                'allergens' => ['gluten', 'dairy', 'eggs'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Filling', 'ar' => 'الحشوة'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Chocolate', 'ar' => 'شوكولاتة'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Almond Cream', 'ar' => 'كريمة اللوز'], 'price_modifier' => 4.00],
                            ['name' => ['en' => 'Ham & Cheese', 'ar' => 'لحم وجبن'], 'price_modifier' => 6.00],
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
     * Create sandwiches.
     */
    private function createSandwiches(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Sandwiches & Wraps')->first();
        $nationality = $nationalities->where('name->en', 'Fast Food')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'Grilled Chicken Panini', 'ar' => 'بانيني الدجاج المشوي'],
                'description' => ['en' => 'Grilled chicken breast with pesto, mozzarella, and sun-dried tomatoes on ciabatta bread', 'ar' => 'صدر دجاج مشوي مع البيستو والموزاريلا والطماطم المجففة على خبز الشياباتا'],
                'base_price' => 28.00,
                'background_type' => 'color',
                'background_value' => '#CD853F',
                'preparation_time' => 8,
                'calories' => 420,
                'ingredients' => ['grilled chicken', 'ciabatta bread', 'pesto', 'mozzarella', 'sun-dried tomatoes'],
                'allergens' => ['gluten', 'dairy'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Side', 'ar' => 'الطبق الجانبي'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Potato Chips', 'ar' => 'رقائق البطاطس'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Side Salad', 'ar' => 'سلطة جانبية'], 'price_modifier' => 8.00],
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
     * Create sweet treats.
     */
    private function createSweetTreats(int $merchantId, $categories, $nationalities): void
    {
        $category = $categories->where('name->en', 'Sweet Treats')->first();
        $nationality = $nationalities->where('name->en', 'Eastern Sweets')->first();

        if (!$category || !$nationality) return;

        $products = [
            [
                'name' => ['en' => 'New York Cheesecake', 'ar' => 'تشيز كيك نيويورك'],
                'description' => ['en' => 'Rich and creamy cheesecake with graham cracker crust and berry compote', 'ar' => 'تشيز كيك غني وكريمي مع قاعدة بسكويت الجراهام وكومبوت التوت'],
                'base_price' => 22.00,
                'background_type' => 'color',
                'background_value' => '#FFE4E1',
                'preparation_time' => 3,
                'is_vegetarian' => true,
                'calories' => 380,
                'ingredients' => ['cream cheese', 'graham crackers', 'eggs', 'sugar', 'berries'],
                'allergens' => ['gluten', 'dairy', 'eggs'],
                'options' => [
                    [
                        'group_name' => ['en' => 'Topping', 'ar' => 'الإضافة العلوية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 2,
                        'options' => [
                            ['name' => ['en' => 'Fresh Berries', 'ar' => 'توت طازج'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Chocolate Sauce', 'ar' => 'صوص الشوكولاتة'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Whipped Cream', 'ar' => 'كريمة مخفوقة'], 'price_modifier' => 3.00],
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
            'sku' => 'CAFE-' . strtoupper(substr(md5(uniqid()), 0, 8)),
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
                'image_path' => 'product_images/cafe_' . $productId . '_main.jpg',
                'alt_text' => ['en' => 'Product main image', 'ar' => 'الصورة الرئيسية للمنتج'],
                'is_primary' => true,
                'sort_order' => 1,
            ],
            [
                'image_path' => 'product_images/cafe_' . $productId . '_detail.jpg',
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
