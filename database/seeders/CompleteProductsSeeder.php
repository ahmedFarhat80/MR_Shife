<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\OptionGroup;
use App\Models\Option;
use App\Models\Merchant;
use App\Models\InternalCategory;
use App\Models\FoodNationality;
use App\Models\ProductImage;

class CompleteProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🍽️ Creating complete products with options and sizes...');

        // Get required data
        $merchants = Merchant::all();
        $categories = InternalCategory::all();
        $foodNationalities = FoodNationality::all();

        if ($merchants->isEmpty() || $categories->isEmpty() || $foodNationalities->isEmpty()) {
            $this->command->error('❌ Required data missing. Please run other seeders first.');
            return;
        }

        // Clear existing products and their options
        $this->command->info('🗑️ Clearing existing products...');
        Product::truncate();
        OptionGroup::truncate();
        Option::truncate();
        ProductImage::truncate();

        // Create products with their complete options
        $this->createCompleteProducts($merchants, $categories, $foodNationalities);

        $this->command->info('✅ Complete products created successfully!');
    }

    /**
     * Create complete products with options
     */
    private function createCompleteProducts($merchants, $categories, $foodNationalities): void
    {
        $productsData = $this->getProductsData();

        foreach ($productsData as $productData) {
            $merchant = $merchants->random();
            $category = $categories->where('name->ar', $productData['category'])->first() ?? $categories->random();
            $foodNationality = $foodNationalities->where('name->ar', $productData['nationality'])->first() ?? $foodNationalities->random();

            // Create the product
            $product = Product::create([
                'merchant_id' => $merchant->id,
                'internal_category_id' => $category->id,
                'food_nationality_id' => $foodNationality->id,
                'name' => [
                    'ar' => $productData['name_ar'],
                    'en' => $productData['name_en']
                ],
                'description' => [
                    'ar' => $productData['description_ar'],
                    'en' => $productData['description_en']
                ],
                'base_price' => $productData['price'],
                'preparation_time' => $productData['preparation_time'],
                'calories' => $productData['calories'],
                'is_available' => true,
                'is_featured' => rand(0, 1),
                'is_popular' => rand(0, 1),
                'average_rating' => round(rand(35, 50) / 10, 1),
                'total_orders' => rand(50, 300),
                'background_type' => 'color',
                'background_value' => '#' . substr(md5(rand()), 0, 6), // Random color
            ]);

            // Create product images
            $this->createProductImages($product, $productData['images']);

            // Create sizes for this product
            $this->createSizesForProduct($product, $productData['sizes']);

            // Create additional options for this product
            $this->createAdditionalOptionsForProduct($product, $productData['options']);

            $this->command->info("✅ Created: {$productData['name_ar']} with " . count($productData['sizes']) . " sizes and " . count($productData['options']) . " option groups");
        }
    }

    /**
     * Create product images
     */
    private function createProductImages(Product $product, array $images): void
    {
        foreach ($images as $index => $imagePath) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'alt_text' => $product->name,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * Create sizes for product
     */
    private function createSizesForProduct(Product $product, array $sizes): void
    {
        if (empty($sizes)) return;

        $sizeGroup = OptionGroup::create([
            'product_id' => $product->id,
            'name' => [
                'ar' => 'الحجم',
                'en' => 'Size'
            ],
            'type' => 'size',
            'is_required' => true,
            'min_selections' => 1,
            'max_selections' => 1,
            'sort_order' => 1,
        ]);

        foreach ($sizes as $index => $size) {
            Option::create([
                'option_group_id' => $sizeGroup->id,
                'name' => [
                    'ar' => $size['name_ar'],
                    'en' => $size['name_en']
                ],
                'price_modifier' => $size['price_modifier'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * Create additional options for product
     */
    private function createAdditionalOptionsForProduct(Product $product, array $optionGroups): void
    {
        foreach ($optionGroups as $groupIndex => $groupData) {
            $optionGroup = OptionGroup::create([
                'product_id' => $product->id,
                'name' => [
                    'ar' => $groupData['name_ar'],
                    'en' => $groupData['name_en']
                ],
                'type' => $groupData['type'],
                'is_required' => $groupData['is_required'],
                'min_selections' => $groupData['min_selections'],
                'max_selections' => $groupData['max_selections'],
                'sort_order' => $groupIndex + 2, // Start from 2 because size is 1
            ]);

            foreach ($groupData['options'] as $optionIndex => $option) {
                Option::create([
                    'option_group_id' => $optionGroup->id,
                    'name' => [
                        'ar' => $option['name_ar'],
                        'en' => $option['name_en']
                    ],
                    'price_modifier' => $option['price_modifier'],
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }
    }

    /**
     * Get products data with their complete options
     */
    private function getProductsData(): array
    {
        return [
            // 🍔 برجر دجاج
            [
                'name_ar' => 'برجر الدجاج الكلاسيكي',
                'name_en' => 'Classic Chicken Burger',
                'description_ar' => 'برجر دجاج طازج مع الخضروات والصوص الخاص',
                'description_en' => 'Fresh chicken burger with vegetables and special sauce',
                'price' => 25.50,
                'preparation_time' => 15,
                'calories' => 450,
                'category' => 'الأطباق الرئيسية',
                'nationality' => 'غربي',
                'images' => [
                    'products/burger-chicken-1.jpg',
                    'products/burger-chicken-2.jpg'
                ],
                'sizes' => [
                    ['name_ar' => 'عادي', 'name_en' => 'Regular', 'price_modifier' => 0.00],
                    ['name_ar' => 'كبير', 'name_en' => 'Large', 'price_modifier' => 5.00],
                    ['name_ar' => 'جامبو', 'name_en' => 'Jumbo', 'price_modifier' => 10.00],
                ],
                'options' => [
                    [
                        'name_ar' => 'درجة النضج',
                        'name_en' => 'Cooking Level',
                        'type' => 'customization',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name_ar' => 'متوسط النضج', 'name_en' => 'Medium', 'price_modifier' => 0.00],
                            ['name_ar' => 'مطبوخ جيداً', 'name_en' => 'Well Done', 'price_modifier' => 0.00],
                        ]
                    ],
                    [
                        'name_ar' => 'إضافات الجبن',
                        'name_en' => 'Cheese Add-ons',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name_ar' => 'جبن شيدر', 'name_en' => 'Cheddar Cheese', 'price_modifier' => 3.00],
                            ['name_ar' => 'جبن سويسري', 'name_en' => 'Swiss Cheese', 'price_modifier' => 3.50],
                            ['name_ar' => 'جبن أزرق', 'name_en' => 'Blue Cheese', 'price_modifier' => 4.00],
                        ]
                    ],
                    [
                        'name_ar' => 'الخضروات والإضافات',
                        'name_en' => 'Vegetables & Extras',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 5,
                        'options' => [
                            ['name_ar' => 'خس إضافي', 'name_en' => 'Extra Lettuce', 'price_modifier' => 1.00],
                            ['name_ar' => 'طماطم إضافية', 'name_en' => 'Extra Tomatoes', 'price_modifier' => 1.50],
                            ['name_ar' => 'أفوكادو', 'name_en' => 'Avocado', 'price_modifier' => 4.00],
                            ['name_ar' => 'بصل مقلي', 'name_en' => 'Fried Onions', 'price_modifier' => 2.00],
                            ['name_ar' => 'فلفل حار', 'name_en' => 'Hot Peppers', 'price_modifier' => 1.50],
                        ]
                    ]
                ]
            ],

            // 🍕 بيتزا مارجريتا
            [
                'name_ar' => 'بيتزا مارجريتا',
                'name_en' => 'Margherita Pizza',
                'description_ar' => 'بيتزا كلاسيكية بالطماطم والجبن والريحان',
                'description_en' => 'Classic pizza with tomatoes, cheese and basil',
                'price' => 35.00,
                'preparation_time' => 20,
                'calories' => 280,
                'category' => 'الأطباق الرئيسية',
                'nationality' => 'إيطالي',
                'images' => [
                    'products/pizza-margherita-1.jpg',
                    'products/pizza-margherita-2.jpg'
                ],
                'sizes' => [
                    ['name_ar' => 'شخصية (6 بوصة)', 'name_en' => 'Personal (6")', 'price_modifier' => 0.00],
                    ['name_ar' => 'صغيرة (9 بوصة)', 'name_en' => 'Small (9")', 'price_modifier' => 8.00],
                    ['name_ar' => 'وسط (12 بوصة)', 'name_en' => 'Medium (12")', 'price_modifier' => 15.00],
                    ['name_ar' => 'كبيرة (15 بوصة)', 'name_en' => 'Large (15")', 'price_modifier' => 25.00],
                    ['name_ar' => 'عائلية (18 بوصة)', 'name_en' => 'Family (18")', 'price_modifier' => 35.00],
                ],
                'options' => [
                    [
                        'name_ar' => 'نوع العجينة',
                        'name_en' => 'Crust Type',
                        'type' => 'customization',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name_ar' => 'عجينة رفيعة', 'name_en' => 'Thin Crust', 'price_modifier' => 0.00],
                            ['name_ar' => 'عجينة سميكة', 'name_en' => 'Thick Crust', 'price_modifier' => 3.00],
                            ['name_ar' => 'عجينة محشوة بالجبن', 'name_en' => 'Cheese Stuffed Crust', 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'name_ar' => 'إضافات الجبن',
                        'name_en' => 'Extra Cheese',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name_ar' => 'جبن موزاريلا إضافي', 'name_en' => 'Extra Mozzarella', 'price_modifier' => 5.00],
                            ['name_ar' => 'جبن شيدر', 'name_en' => 'Cheddar Cheese', 'price_modifier' => 4.00],
                            ['name_ar' => 'جبن بارميزان', 'name_en' => 'Parmesan Cheese', 'price_modifier' => 6.00],
                        ]
                    ],
                    [
                        'name_ar' => 'خضروات إضافية',
                        'name_en' => 'Extra Vegetables',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 5,
                        'options' => [
                            ['name_ar' => 'فطر', 'name_en' => 'Mushrooms', 'price_modifier' => 2.00],
                            ['name_ar' => 'فلفل أخضر', 'name_en' => 'Green Peppers', 'price_modifier' => 2.00],
                            ['name_ar' => 'بصل', 'name_en' => 'Onions', 'price_modifier' => 1.50],
                            ['name_ar' => 'طماطم', 'name_en' => 'Tomatoes', 'price_modifier' => 2.00],
                            ['name_ar' => 'زيتون أسود', 'name_en' => 'Black Olives', 'price_modifier' => 3.00],
                        ]
                    ]
                ]
            ],

            // ☕ قهوة عربية
            [
                'name_ar' => 'قهوة عربية مميزة',
                'name_en' => 'Premium Arabic Coffee',
                'description_ar' => 'قهوة عربية أصيلة محمصة طازجة',
                'description_en' => 'Authentic Arabic coffee freshly roasted',
                'price' => 12.00,
                'preparation_time' => 5,
                'calories' => 5,
                'category' => 'المشروبات',
                'nationality' => 'عربي',
                'images' => [
                    'products/arabic-coffee-1.jpg'
                ],
                'sizes' => [
                    ['name_ar' => 'صغير', 'name_en' => 'Small', 'price_modifier' => 0.00],
                    ['name_ar' => 'وسط', 'name_en' => 'Medium', 'price_modifier' => 3.00],
                    ['name_ar' => 'كبير', 'name_en' => 'Large', 'price_modifier' => 6.00],
                ],
                'options' => [
                    [
                        'name_ar' => 'مستوى التحميص',
                        'name_en' => 'Roast Level',
                        'type' => 'customization',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 1,
                        'options' => [
                            ['name_ar' => 'تحميص خفيف', 'name_en' => 'Light Roast', 'price_modifier' => 0.00],
                            ['name_ar' => 'تحميص متوسط', 'name_en' => 'Medium Roast', 'price_modifier' => 0.00],
                            ['name_ar' => 'تحميص غامق', 'name_en' => 'Dark Roast', 'price_modifier' => 0.00],
                        ]
                    ],
                    [
                        'name_ar' => 'إضافات',
                        'name_en' => 'Add-ons',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name_ar' => 'هيل إضافي', 'name_en' => 'Extra Cardamom', 'price_modifier' => 1.00],
                            ['name_ar' => 'زعفران', 'name_en' => 'Saffron', 'price_modifier' => 5.00],
                            ['name_ar' => 'ماء ورد', 'name_en' => 'Rose Water', 'price_modifier' => 2.00],
                        ]
                    ]
                ]
            ],

            // 🍰 كيك الشوكولاتة
            [
                'name_ar' => 'كيك الشوكولاتة الفاخر',
                'name_en' => 'Luxury Chocolate Cake',
                'description_ar' => 'كيك شوكولاتة غني بالكريمة والفواكه',
                'description_en' => 'Rich chocolate cake with cream and fruits',
                'price' => 45.00,
                'preparation_time' => 10,
                'calories' => 520,
                'category' => 'الحلويات',
                'nationality' => 'غربي',
                'images' => [
                    'products/chocolate-cake-1.jpg',
                    'products/chocolate-cake-2.jpg'
                ],
                'sizes' => [
                    ['name_ar' => 'قطعة واحدة', 'name_en' => 'Single Slice', 'price_modifier' => 0.00],
                    ['name_ar' => 'نصف كيك', 'name_en' => 'Half Cake', 'price_modifier' => 120.00],
                    ['name_ar' => 'كيك كامل', 'name_en' => 'Whole Cake', 'price_modifier' => 200.00],
                ],
                'options' => [
                    [
                        'name_ar' => 'إضافات الكريمة',
                        'name_en' => 'Cream Add-ons',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 3,
                        'options' => [
                            ['name_ar' => 'كريمة مخفوقة', 'name_en' => 'Whipped Cream', 'price_modifier' => 5.00],
                            ['name_ar' => 'آيس كريم فانيليا', 'name_en' => 'Vanilla Ice Cream', 'price_modifier' => 8.00],
                            ['name_ar' => 'آيس كريم شوكولاتة', 'name_en' => 'Chocolate Ice Cream', 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'name_ar' => 'إضافات الفواكه',
                        'name_en' => 'Fruit Add-ons',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 4,
                        'options' => [
                            ['name_ar' => 'فراولة طازجة', 'name_en' => 'Fresh Strawberries', 'price_modifier' => 6.00],
                            ['name_ar' => 'موز', 'name_en' => 'Banana', 'price_modifier' => 4.00],
                            ['name_ar' => 'توت أزرق', 'name_en' => 'Blueberries', 'price_modifier' => 8.00],
                            ['name_ar' => 'كيوي', 'name_en' => 'Kiwi', 'price_modifier' => 7.00],
                        ]
                    ]
                ]
            ],

            // 🥗 سلطة سيزر
            [
                'name_ar' => 'سلطة سيزر كلاسيك',
                'name_en' => 'Classic Caesar Salad',
                'description_ar' => 'سلطة سيزر طازجة مع الدجاج المشوي',
                'description_en' => 'Fresh Caesar salad with grilled chicken',
                'price' => 28.00,
                'preparation_time' => 8,
                'calories' => 180,
                'category' => 'السلطات',
                'nationality' => 'غربي',
                'images' => [
                    'products/caesar-salad-1.jpg'
                ],
                'sizes' => [
                    ['name_ar' => 'صغير', 'name_en' => 'Small', 'price_modifier' => 0.00],
                    ['name_ar' => 'وسط', 'name_en' => 'Medium', 'price_modifier' => 8.00],
                    ['name_ar' => 'كبير', 'name_en' => 'Large', 'price_modifier' => 15.00],
                ],
                'options' => [
                    [
                        'name_ar' => 'نوع البروتين',
                        'name_en' => 'Protein Type',
                        'type' => 'customization',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 1,
                        'options' => [
                            ['name_ar' => 'دجاج مشوي', 'name_en' => 'Grilled Chicken', 'price_modifier' => 0.00],
                            ['name_ar' => 'جمبري', 'name_en' => 'Shrimp', 'price_modifier' => 12.00],
                            ['name_ar' => 'سلمون', 'name_en' => 'Salmon', 'price_modifier' => 18.00],
                            ['name_ar' => 'بدون بروتين', 'name_en' => 'No Protein', 'price_modifier' => -8.00],
                        ]
                    ],
                    [
                        'name_ar' => 'إضافات',
                        'name_en' => 'Add-ons',
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 4,
                        'options' => [
                            ['name_ar' => 'جبن بارميزان إضافي', 'name_en' => 'Extra Parmesan', 'price_modifier' => 4.00],
                            ['name_ar' => 'خبز محمص إضافي', 'name_en' => 'Extra Croutons', 'price_modifier' => 2.00],
                            ['name_ar' => 'أفوكادو', 'name_en' => 'Avocado', 'price_modifier' => 6.00],
                            ['name_ar' => 'طماطم كرزية', 'name_en' => 'Cherry Tomatoes', 'price_modifier' => 3.00],
                        ]
                    ]
                ]
            ]
        ];
    }
}
