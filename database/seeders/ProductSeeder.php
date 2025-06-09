<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Merchant;
use App\Models\InternalCategory;
use App\Models\FoodNationality;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🍽️ Starting Enhanced Product Seeding...');

        // Get all merchants
        $merchants = Merchant::where('status', 'active')->get();

        if ($merchants->isEmpty()) {
            $this->command->error('No active merchants found. Please run MerchantSeeder first.');
            return;
        }

        // Get or create food nationalities
        $nationalities = $this->createFoodNationalities();

        foreach ($merchants as $merchant) {
            $this->command->info("Creating products for: " . json_encode($merchant->business_name));

            // Create categories for this merchant
            $categories = $this->createCategoriesForMerchant($merchant);

            // Create products based on merchant type
            $this->createProductsForMerchant($merchant, $categories, $nationalities);
        }

        $this->command->info('✅ Enhanced Product Seeding completed successfully!');
    }

    /**
     * Create food nationalities.
     */
    private function createFoodNationalities(): array
    {
        $nationalities = [
            ['name' => ['en' => 'Arabic', 'ar' => 'عربي'], 'is_active' => true],
            ['name' => ['en' => 'Italian', 'ar' => 'إيطالي'], 'is_active' => true],
            ['name' => ['en' => 'American', 'ar' => 'أمريكي'], 'is_active' => true],
            ['name' => ['en' => 'Asian', 'ar' => 'آسيوي'], 'is_active' => true],
            ['name' => ['en' => 'Mediterranean', 'ar' => 'متوسطي'], 'is_active' => true],
            ['name' => ['en' => 'Mexican', 'ar' => 'مكسيكي'], 'is_active' => true],
        ];

        $result = [];
        foreach ($nationalities as $nationalityData) {
            $result[] = FoodNationality::firstOrCreate(
                ['name' => $nationalityData['name']],
                $nationalityData
            );
        }

        return $result;
    }

    /**
     * Create categories for merchant.
     */
    private function createCategoriesForMerchant(Merchant $merchant): array
    {
        $businessName = is_array($merchant->business_name)
            ? ($merchant->business_name['en'] ?? $merchant->business_name['ar'] ?? 'Restaurant')
            : $merchant->business_name;

        // Determine merchant type from business name
        $merchantType = $this->determineMerchantType($businessName);

        $categories = $this->getCategoriesForType($merchantType);

        $result = [];
        foreach ($categories as $index => $categoryData) {
            $result[] = InternalCategory::firstOrCreate([
                'merchant_id' => $merchant->id,
                'name' => $categoryData['name'],
            ], [
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }

        return $result;
    }

    /**
     * Determine merchant type from business name.
     */
    private function determineMerchantType(string $businessName): string
    {
        $businessName = strtolower($businessName);

        if (str_contains($businessName, 'cafe') || str_contains($businessName, 'coffee')) {
            return 'cafe';
        } elseif (str_contains($businessName, 'pizza')) {
            return 'pizza';
        } elseif (str_contains($businessName, 'burger')) {
            return 'burger';
        } elseif (str_contains($businessName, 'traditional') || str_contains($businessName, 'arabic')) {
            return 'arabic';
        } elseif (str_contains($businessName, 'italian')) {
            return 'italian';
        } elseif (str_contains($businessName, 'asian') || str_contains($businessName, 'sushi')) {
            return 'asian';
        } else {
            return 'general';
        }
    }

    /**
     * Get categories for merchant type.
     */
    private function getCategoriesForType(string $type): array
    {
        $categories = [
            'cafe' => [
                ['name' => ['en' => 'Hot Drinks', 'ar' => 'المشروبات الساخنة'], 'description' => ['en' => 'Coffee, tea, and hot beverages', 'ar' => 'القهوة والشاي والمشروبات الساخنة']],
                ['name' => ['en' => 'Cold Drinks', 'ar' => 'المشروبات الباردة'], 'description' => ['en' => 'Iced coffee, smoothies, and cold beverages', 'ar' => 'القهوة المثلجة والعصائر والمشروبات الباردة']],
                ['name' => ['en' => 'Pastries', 'ar' => 'المعجنات'], 'description' => ['en' => 'Fresh baked goods and pastries', 'ar' => 'المخبوزات والمعجنات الطازجة']],
                ['name' => ['en' => 'Desserts', 'ar' => 'الحلويات'], 'description' => ['en' => 'Cakes, cookies, and sweet treats', 'ar' => 'الكيك والبسكويت والحلويات']],
            ],
            'pizza' => [
                ['name' => ['en' => 'Classic Pizzas', 'ar' => 'البيتزا الكلاسيكية'], 'description' => ['en' => 'Traditional pizza varieties', 'ar' => 'أنواع البيتزا التقليدية']],
                ['name' => ['en' => 'Specialty Pizzas', 'ar' => 'البيتزا المميزة'], 'description' => ['en' => 'Gourmet and specialty pizzas', 'ar' => 'البيتزا الفاخرة والمميزة']],
                ['name' => ['en' => 'Appetizers', 'ar' => 'المقبلات'], 'description' => ['en' => 'Starters and side dishes', 'ar' => 'المقبلات والأطباق الجانبية']],
                ['name' => ['en' => 'Beverages', 'ar' => 'المشروبات'], 'description' => ['en' => 'Soft drinks and beverages', 'ar' => 'المشروبات الغازية والعصائر']],
            ],
            'burger' => [
                ['name' => ['en' => 'Beef Burgers', 'ar' => 'برجر اللحم'], 'description' => ['en' => 'Juicy beef burger varieties', 'ar' => 'أنواع برجر اللحم الشهية']],
                ['name' => ['en' => 'Chicken Burgers', 'ar' => 'برجر الدجاج'], 'description' => ['en' => 'Grilled and crispy chicken burgers', 'ar' => 'برجر الدجاج المشوي والمقرمش']],
                ['name' => ['en' => 'Sides', 'ar' => 'الأطباق الجانبية'], 'description' => ['en' => 'Fries, onion rings, and sides', 'ar' => 'البطاطس المقلية وحلقات البصل والأطباق الجانبية']],
                ['name' => ['en' => 'Beverages', 'ar' => 'المشروبات'], 'description' => ['en' => 'Soft drinks and shakes', 'ar' => 'المشروبات الغازية والميلك شيك']],
            ],
            'arabic' => [
                ['name' => ['en' => 'Main Dishes', 'ar' => 'الأطباق الرئيسية'], 'description' => ['en' => 'Traditional Arabic main courses', 'ar' => 'الأطباق العربية الرئيسية التقليدية']],
                ['name' => ['en' => 'Grilled Items', 'ar' => 'المشاوي'], 'description' => ['en' => 'Grilled meats and kebabs', 'ar' => 'اللحوم المشوية والكباب']],
                ['name' => ['en' => 'Rice Dishes', 'ar' => 'أطباق الأرز'], 'description' => ['en' => 'Kabsa, biryani, and rice specialties', 'ar' => 'الكبسة والبرياني وأطباق الأرز المميزة']],
                ['name' => ['en' => 'Appetizers', 'ar' => 'المقبلات'], 'description' => ['en' => 'Hummus, tabbouleh, and starters', 'ar' => 'الحمص والتبولة والمقبلات']],
                ['name' => ['en' => 'Beverages', 'ar' => 'المشروبات'], 'description' => ['en' => 'Traditional drinks and juices', 'ar' => 'المشروبات التقليدية والعصائر']],
            ],
            'italian' => [
                ['name' => ['en' => 'Pasta', 'ar' => 'المعكرونة'], 'description' => ['en' => 'Fresh pasta dishes', 'ar' => 'أطباق المعكرونة الطازجة']],
                ['name' => ['en' => 'Risotto', 'ar' => 'الريزوتو'], 'description' => ['en' => 'Creamy Italian rice dishes', 'ar' => 'أطباق الأرز الإيطالية الكريمية']],
                ['name' => ['en' => 'Antipasti', 'ar' => 'المقبلات'], 'description' => ['en' => 'Italian appetizers and starters', 'ar' => 'المقبلات الإيطالية']],
                ['name' => ['en' => 'Desserts', 'ar' => 'الحلويات'], 'description' => ['en' => 'Tiramisu, gelato, and Italian sweets', 'ar' => 'التيراميسو والجيلاتو والحلويات الإيطالية']],
            ],
            'asian' => [
                ['name' => ['en' => 'Sushi & Sashimi', 'ar' => 'السوشي والساشيمي'], 'description' => ['en' => 'Fresh sushi and sashimi', 'ar' => 'السوشي والساشيمي الطازج']],
                ['name' => ['en' => 'Noodles', 'ar' => 'النودلز'], 'description' => ['en' => 'Ramen, pad thai, and noodle dishes', 'ar' => 'الرامن والباد تاي وأطباق النودلز']],
                ['name' => ['en' => 'Stir Fry', 'ar' => 'المقلي'], 'description' => ['en' => 'Wok-fried dishes and stir fries', 'ar' => 'الأطباق المقلية في الووك']],
                ['name' => ['en' => 'Appetizers', 'ar' => 'المقبلات'], 'description' => ['en' => 'Spring rolls, dumplings, and starters', 'ar' => 'لفائف الربيع والزلابية والمقبلات']],
            ],
            'general' => [
                ['name' => ['en' => 'Main Dishes', 'ar' => 'الأطباق الرئيسية'], 'description' => ['en' => 'Main course dishes', 'ar' => 'الأطباق الرئيسية']],
                ['name' => ['en' => 'Appetizers', 'ar' => 'المقبلات'], 'description' => ['en' => 'Starters and appetizers', 'ar' => 'المقبلات والبدايات']],
                ['name' => ['en' => 'Desserts', 'ar' => 'الحلويات'], 'description' => ['en' => 'Sweet treats and desserts', 'ar' => 'الحلويات والأطباق الحلوة']],
                ['name' => ['en' => 'Beverages', 'ar' => 'المشروبات'], 'description' => ['en' => 'Drinks and beverages', 'ar' => 'المشروبات والعصائر']],
            ],
        ];

        return $categories[$type] ?? $categories['general'];
    }

    /**
     * Create products for merchant.
     */
    private function createProductsForMerchant(Merchant $merchant, array $categories, array $nationalities): void
    {
        $businessName = is_array($merchant->business_name)
            ? ($merchant->business_name['en'] ?? $merchant->business_name['ar'] ?? 'Restaurant')
            : $merchant->business_name;

        $merchantType = $this->determineMerchantType($businessName);
        $products = $this->getProductsForType($merchantType);

        $featuredCount = 0;
        $popularCount = 0;
        $maxFeatured = 3;
        $maxPopular = 4;

        foreach ($categories as $categoryIndex => $category) {
            $categoryProducts = $products[$categoryIndex] ?? [];

            foreach ($categoryProducts as $productData) {
                $isFeatured = $featuredCount < $maxFeatured && rand(1, 3) === 1;
                $isPopular = $popularCount < $maxPopular && rand(1, 3) === 1;

                if ($isFeatured) $featuredCount++;
                if ($isPopular) $popularCount++;

                // Select appropriate nationality
                $nationality = $this->selectNationalityForProduct($productData, $nationalities, $merchantType);

                Product::create([
                    'merchant_id' => $merchant->id,
                    'internal_category_id' => $category->id,
                    'food_nationality_id' => $nationality->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'base_price' => $productData['base_price'],
                    'discount_percentage' => $productData['discount_percentage'] ?? null,
                    'discounted_price' => $productData['discounted_price'] ?? null,
                    'is_available' => true,
                    'is_featured' => $isFeatured,
                    'is_popular' => $isPopular,
                    'is_vegetarian' => $productData['is_vegetarian'] ?? false,
                    'is_spicy' => $productData['is_spicy'] ?? false,
                    'total_orders' => rand(10, 300),
                    'average_rating' => rand(35, 50) / 10, // 3.5 to 5.0
                    'preparation_time' => $productData['preparation_time'] ?? rand(10, 45),
                    'calories' => $productData['calories'] ?? rand(150, 800),
                ]);
            }
        }

        $this->command->info("  ✅ Created products for {$businessName}");
    }

    /**
     * Select appropriate nationality for product.
     */
    private function selectNationalityForProduct(array $productData, array $nationalities, string $merchantType): FoodNationality
    {
        $nationalityMap = [
            'arabic' => 'Arabic',
            'italian' => 'Italian',
            'asian' => 'Asian',
            'pizza' => 'Italian',
            'burger' => 'American',
            'cafe' => 'Mediterranean',
            'general' => 'Mediterranean',
        ];

        $targetNationality = $nationalityMap[$merchantType] ?? 'Mediterranean';

        foreach ($nationalities as $nationality) {
            $name = $nationality->name;
            $englishName = is_array($name) ? ($name['en'] ?? '') : $name;

            if (str_contains($englishName, $targetNationality)) {
                return $nationality;
            }
        }

        return $nationalities[0]; // Fallback to first nationality
    }

    /**
     * Get products for merchant type.
     */
    private function getProductsForType(string $type): array
    {
        $products = [
            'cafe' => [
                // Hot Drinks
                [
                    ['name' => ['en' => 'Espresso', 'ar' => 'إسبريسو'], 'description' => ['en' => 'Strong Italian coffee', 'ar' => 'قهوة إيطالية قوية'], 'base_price' => 12.00, 'preparation_time' => 3],
                    ['name' => ['en' => 'Cappuccino', 'ar' => 'كابتشينو'], 'description' => ['en' => 'Coffee with steamed milk foam', 'ar' => 'قهوة مع رغوة الحليب'], 'base_price' => 15.00, 'preparation_time' => 5],
                    ['name' => ['en' => 'Latte', 'ar' => 'لاتيه'], 'description' => ['en' => 'Coffee with steamed milk', 'ar' => 'قهوة مع الحليب المبخر'], 'base_price' => 16.00, 'preparation_time' => 5],
                ],
                // Cold Drinks
                [
                    ['name' => ['en' => 'Iced Coffee', 'ar' => 'قهوة مثلجة'], 'description' => ['en' => 'Cold brew coffee with ice', 'ar' => 'قهوة باردة مع الثلج'], 'base_price' => 14.00, 'preparation_time' => 3],
                    ['name' => ['en' => 'Frappuccino', 'ar' => 'فرابتشينو'], 'description' => ['en' => 'Blended coffee drink', 'ar' => 'مشروب قهوة مخلوط'], 'base_price' => 18.00, 'preparation_time' => 7],
                ],
                // Pastries
                [
                    ['name' => ['en' => 'Croissant', 'ar' => 'كرواسون'], 'description' => ['en' => 'Buttery French pastry', 'ar' => 'معجنات فرنسية بالزبدة'], 'base_price' => 8.00, 'is_vegetarian' => true],
                    ['name' => ['en' => 'Danish Pastry', 'ar' => 'معجنات دنماركية'], 'description' => ['en' => 'Sweet layered pastry', 'ar' => 'معجنات حلوة متعددة الطبقات'], 'base_price' => 10.00, 'is_vegetarian' => true],
                ],
                // Desserts
                [
                    ['name' => ['en' => 'Cheesecake', 'ar' => 'تشيز كيك'], 'description' => ['en' => 'Creamy cheese dessert', 'ar' => 'حلوى الجبن الكريمية'], 'base_price' => 22.00, 'is_vegetarian' => true],
                    ['name' => ['en' => 'Chocolate Brownie', 'ar' => 'براوني الشوكولاتة'], 'description' => ['en' => 'Rich chocolate dessert', 'ar' => 'حلوى الشوكولاتة الغنية'], 'base_price' => 18.00, 'is_vegetarian' => true],
                ],
            ],
            'arabic' => [
                // Main Dishes
                [
                    ['name' => ['en' => 'Chicken Kabsa', 'ar' => 'كبسة الدجاج'], 'description' => ['en' => 'Traditional rice dish with chicken', 'ar' => 'طبق أرز تقليدي مع الدجاج'], 'base_price' => 35.00, 'preparation_time' => 25],
                    ['name' => ['en' => 'Lamb Mandi', 'ar' => 'مندي اللحم'], 'description' => ['en' => 'Slow-cooked lamb with rice', 'ar' => 'لحم مطبوخ ببطء مع الأرز'], 'base_price' => 45.00, 'preparation_time' => 30],
                    ['name' => ['en' => 'Mixed Grill', 'ar' => 'مشكل مشاوي'], 'description' => ['en' => 'Assorted grilled meats', 'ar' => 'تشكيلة من اللحوم المشوية'], 'base_price' => 55.00, 'preparation_time' => 20],
                ],
                // Grilled Items
                [
                    ['name' => ['en' => 'Chicken Tikka', 'ar' => 'تكا الدجاج'], 'description' => ['en' => 'Marinated grilled chicken', 'ar' => 'دجاج مشوي متبل'], 'base_price' => 28.00, 'preparation_time' => 15],
                    ['name' => ['en' => 'Lamb Kebab', 'ar' => 'كباب اللحم'], 'description' => ['en' => 'Grilled lamb skewers', 'ar' => 'أسياخ لحم مشوية'], 'base_price' => 32.00, 'preparation_time' => 18],
                ],
                // Rice Dishes
                [
                    ['name' => ['en' => 'Biryani', 'ar' => 'برياني'], 'description' => ['en' => 'Spiced rice with meat', 'ar' => 'أرز متبل مع اللحم'], 'base_price' => 38.00, 'is_spicy' => true, 'preparation_time' => 30],
                    ['name' => ['en' => 'Machboos', 'ar' => 'مجبوس'], 'description' => ['en' => 'Gulf-style spiced rice', 'ar' => 'أرز خليجي متبل'], 'base_price' => 33.00, 'preparation_time' => 25],
                ],
                // Appetizers
                [
                    ['name' => ['en' => 'Hummus', 'ar' => 'حمص'], 'description' => ['en' => 'Chickpea dip with tahini', 'ar' => 'غموس الحمص بالطحينة'], 'base_price' => 12.00, 'is_vegetarian' => true],
                    ['name' => ['en' => 'Tabbouleh', 'ar' => 'تبولة'], 'description' => ['en' => 'Fresh parsley salad', 'ar' => 'سلطة البقدونس الطازجة'], 'base_price' => 15.00, 'is_vegetarian' => true],
                ],
                // Beverages
                [
                    ['name' => ['en' => 'Arabic Coffee', 'ar' => 'قهوة عربية'], 'description' => ['en' => 'Traditional cardamom coffee', 'ar' => 'قهوة تقليدية بالهيل'], 'base_price' => 8.00, 'preparation_time' => 5],
                    ['name' => ['en' => 'Fresh Orange Juice', 'ar' => 'عصير برتقال طازج'], 'description' => ['en' => 'Freshly squeezed orange juice', 'ar' => 'عصير برتقال طازج معصور'], 'base_price' => 10.00, 'preparation_time' => 3],
                ],
            ],
        ];

        // Add default products for other types
        $defaultProducts = [
            [
                ['name' => ['en' => 'House Special', 'ar' => 'طبق البيت المميز'], 'description' => ['en' => 'Chef\'s special dish', 'ar' => 'طبق الشيف المميز'], 'base_price' => 30.00],
                ['name' => ['en' => 'Grilled Chicken', 'ar' => 'دجاج مشوي'], 'description' => ['en' => 'Tender grilled chicken', 'ar' => 'دجاج مشوي طري'], 'base_price' => 25.00],
            ],
            [
                ['name' => ['en' => 'Garden Salad', 'ar' => 'سلطة الحديقة'], 'description' => ['en' => 'Fresh mixed greens', 'ar' => 'خضار ورقية طازجة مشكلة'], 'base_price' => 15.00, 'is_vegetarian' => true],
                ['name' => ['en' => 'Soup of the Day', 'ar' => 'شوربة اليوم'], 'description' => ['en' => 'Daily fresh soup', 'ar' => 'شوربة طازجة يومية'], 'base_price' => 12.00],
            ],
            [
                ['name' => ['en' => 'Chocolate Cake', 'ar' => 'كيك الشوكولاتة'], 'description' => ['en' => 'Rich chocolate dessert', 'ar' => 'حلوى الشوكولاتة الغنية'], 'base_price' => 20.00, 'is_vegetarian' => true],
            ],
            [
                ['name' => ['en' => 'Fresh Juice', 'ar' => 'عصير طازج'], 'description' => ['en' => 'Seasonal fresh juice', 'ar' => 'عصير طازج موسمي'], 'base_price' => 8.00],
                ['name' => ['en' => 'Soft Drink', 'ar' => 'مشروب غازي'], 'description' => ['en' => 'Carbonated beverage', 'ar' => 'مشروب غازي'], 'base_price' => 5.00],
            ],
        ];

        return $products[$type] ?? $defaultProducts;
    }
}
