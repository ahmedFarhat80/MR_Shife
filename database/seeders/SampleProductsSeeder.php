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

class SampleProductsSeeder extends Seeder
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

        // Get existing global categories and nationalities
        $categories = InternalCategory::take(4)->get();
        $nationalities = FoodNationality::all();

        if ($categories->isEmpty() || $nationalities->isEmpty()) {
            $this->command->warn('No categories or nationalities found.');
            return;
        }

        // Create sample products
        $this->createSampleProducts($merchant->id, $categories, $nationalities);

        $this->command->info('Sample products created successfully!');
    }



    /**
     * Create sample products with options.
     */
    private function createSampleProducts(int $merchantId, $categories, $nationalities): void
    {
        // Get available categories and nationalities safely
        $mainCategory = $categories->first(); // Use first available category
        $nationality = $nationalities->first(); // Use first available nationality

        if (!$mainCategory || !$nationality) {
            $this->command->warn('No categories or nationalities found for merchant ' . $merchantId);
            return;
        }

        $products = [
            // Burger with size options
            [
                'name' => ['en' => 'Classic Beef Burger', 'ar' => 'برجر اللحم الكلاسيكي'],
                'description' => ['en' => 'Juicy beef patty with fresh vegetables', 'ar' => 'قطعة لحم عصيرة مع خضار طازجة'],
                'internal_category_id' => $mainCategory->id,
                'food_nationality_id' => $nationality->id,
                'background_type' => 'color',
                'background_value' => '#FF6B35',
                'base_price' => 25.00,
                'discount_percentage' => 10.00,
                'preparation_time' => 15,
                'is_featured' => true,
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Regular', 'ar' => 'عادي'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Large', 'ar' => 'كبير'], 'price_modifier' => 5.00],
                            ['name' => ['en' => 'Extra Large', 'ar' => 'كبير جداً'], 'price_modifier' => 8.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Add-ons', 'ar' => 'الإضافات'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 0, // unlimited
                        'options' => [
                            ['name' => ['en' => 'Extra Cheese', 'ar' => 'جبنة إضافية'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Bacon', 'ar' => 'لحم مقدد'], 'price_modifier' => 4.00],
                            ['name' => ['en' => 'Avocado', 'ar' => 'أفوكادو'], 'price_modifier' => 5.00],
                        ]
                    ]
                ]
            ],
            // Pizza with customization
            [
                'name' => ['en' => 'Margherita Pizza', 'ar' => 'بيتزا مارجريتا'],
                'description' => ['en' => 'Classic pizza with tomato, mozzarella and basil', 'ar' => 'بيتزا كلاسيكية بالطماطم والموزاريلا والريحان'],
                'internal_category_id' => $mainCategory->id,
                'food_nationality_id' => $nationality->id,
                'background_type' => 'color',
                'background_value' => '#C41E3A',
                'base_price' => 35.00,
                'preparation_time' => 20,
                'options' => [
                    [
                        'group_name' => ['en' => 'Size', 'ar' => 'الحجم'],
                        'type' => 'size',
                        'is_required' => true,
                        'min_selections' => 1,
                        'max_selections' => 1,
                        'options' => [
                            ['name' => ['en' => 'Small (8")', 'ar' => 'صغير (8 بوصة)'], 'price_modifier' => -5.00],
                            ['name' => ['en' => 'Medium (12")', 'ar' => 'متوسط (12 بوصة)'], 'price_modifier' => 0.00],
                            ['name' => ['en' => 'Large (16")', 'ar' => 'كبير (16 بوصة)'], 'price_modifier' => 10.00],
                        ]
                    ],
                    [
                        'group_name' => ['en' => 'Extra Toppings', 'ar' => 'إضافات إضافية'],
                        'type' => 'addon',
                        'is_required' => false,
                        'min_selections' => 0,
                        'max_selections' => 5,
                        'options' => [
                            ['name' => ['en' => 'Pepperoni', 'ar' => 'ببروني'], 'price_modifier' => 4.00],
                            ['name' => ['en' => 'Mushrooms', 'ar' => 'فطر'], 'price_modifier' => 3.00],
                            ['name' => ['en' => 'Olives', 'ar' => 'زيتون'], 'price_modifier' => 2.00],
                            ['name' => ['en' => 'Bell Peppers', 'ar' => 'فلفل حلو'], 'price_modifier' => 2.50],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($products as $productData) {
            $this->createProductWithOptions($merchantId, $productData);
        }
    }

    /**
     * Create a product with its options.
     */
    private function createProductWithOptions(int $merchantId, array $productData): void
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
        // Create placeholder images
        $images = [
            [
                'image_path' => 'product_images/sample_1.jpg',
                'alt_text' => ['en' => 'Product main image', 'ar' => 'الصورة الرئيسية للمنتج'],
                'is_primary' => true,
                'sort_order' => 1,
            ],
            [
                'image_path' => 'product_images/sample_2.jpg',
                'alt_text' => ['en' => 'Product secondary image', 'ar' => 'الصورة الثانوية للمنتج'],
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
