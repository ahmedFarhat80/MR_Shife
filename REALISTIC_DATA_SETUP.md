# Realistic Product Management System Setup

This guide will help you set up the complete product management system with realistic sample data.

## 🚀 Quick Setup

### 1. Run Migrations
```bash
php artisan migrate:fresh
```

### 2. Seed the Database
```bash
php artisan db:seed
```

This will create:
- **2 Realistic Merchants** (Restaurant & Cafe)
- **2 Sample Customers** with complete profiles
- **Food Nationalities** (10 categories)
- **Internal Categories** specific to each merchant type
- **Realistic Products** with options and images

## 📊 What Gets Created

### Merchants
1. **Al Salam Restaurant** (مطعم السلام)
   - Business Type: Restaurant
   - Subscription: Premium Plan
   - Location: Al Olaya, Riyadh
   - Phone: +966501234567
   - Status: Active & Verified

2. **Coffee Corner Cafe** (مقهى ركن القهوة)
   - Business Type: Cafe
   - Subscription: Basic Plan
   - Location: Al Malaz, Riyadh
   - Phone: +966507654321
   - Status: Active & Verified

### Customers
1. **Ahmed Al-Rashid** (أحمد الراشد)
   - Phone: +966501111111
   - Language: Arabic
   - Multiple addresses (Home & Work)
   - Loyalty Points: 150

2. **Sarah Johnson** (سارة جونسون)
   - Phone: +966502222222
   - Language: English
   - Villa address in Al Nakheel
   - Loyalty Points: 75

### Restaurant Products (Al Salam Restaurant)
- **Cold Appetizers**: Mixed Arabic Appetizers, Fresh Fattoush Salad
- **Hot Appetizers**: Crispy Kibbeh
- **Grilled Meats**: Mixed Grill Platter
- **Rice Dishes**: Chicken Kabsa
- **Beverages**: Fresh Orange Juice
- **Desserts**: Kunafa with Cheese

### Cafe Products (Coffee Corner Cafe)
- **Espresso Based**: Signature Cappuccino, Caffe Latte
- **Cold Coffee**: Iced Caramel Macchiato
- **Tea Selection**: Earl Grey Tea
- **Pastries**: Butter Croissant
- **Sandwiches**: Grilled Chicken Panini
- **Sweet Treats**: New York Cheesecake

## 🎯 Product Features

### Each Product Includes:
- ✅ **Multilingual Names & Descriptions** (Arabic & English)
- ✅ **Realistic Pricing** with discounts
- ✅ **Preparation Times**
- ✅ **Nutritional Information** (calories)
- ✅ **Ingredients & Allergens**
- ✅ **Background Colors/Images**
- ✅ **Multiple Product Images**
- ✅ **Complex Option Groups**:
  - Size options (Small, Medium, Large)
  - Customization options (Spice level, milk type)
  - Add-on options (Extra ingredients, sides)
  - Ingredient options (Bread type, sauce)

### Option Group Types:
- **Size**: Different portion sizes with price modifiers
- **Addon**: Extra items that can be added
- **Ingredient**: Core ingredient choices
- **Customization**: Preparation preferences

## 🗂️ Database Structure

### Core Tables:
- `merchants` - Business information
- `customers` - Customer profiles
- `food_nationalities` - Cuisine types
- `internal_categories` - Merchant-specific categories
- `products` - Main product data
- `product_images` - Product photos
- `option_groups` - Product customization groups
- `options` - Individual customization options

### Key Relationships:
- Products belong to Merchants
- Products have Internal Categories
- Products have Food Nationalities
- Products have multiple Images
- Products have multiple Option Groups
- Option Groups have multiple Options

## 🔧 Testing the System

### Check Merchants:
```bash
php artisan tinker
>>> App\Models\Merchant::with('internalCategories')->get()
```

### Check Products:
```bash
>>> App\Models\Product::with(['merchant', 'internalCategory', 'foodNationality', 'images', 'optionGroups.options'])->get()
```

### Check Restaurant Products:
```bash
>>> $restaurant = App\Models\Merchant::where('business_type', 'restaurant')->first()
>>> $restaurant->products()->with(['internalCategory', 'optionGroups.options'])->get()
```

### Check Cafe Products:
```bash
>>> $cafe = App\Models\Merchant::where('business_type', 'cafe')->first()
>>> $cafe->products()->with(['internalCategory', 'optionGroups.options'])->get()
```

## 📱 API Testing

The system is ready for API testing with realistic data. You can:

1. **Test Product Listing** for each merchant
2. **Test Product Filtering** by category/nationality
3. **Test Product Search** functionality
4. **Test Option Selection** and price calculation
5. **Test Image Management** features

## 🎨 Customization

### Adding More Products:
1. Create new seeders following the pattern
2. Add them to `DatabaseSeeder.php`
3. Run `php artisan db:seed --class=YourSeeder`

### Modifying Existing Data:
1. Edit the seeder files
2. Run `php artisan migrate:fresh --seed` to reset

## 📋 Next Steps

1. **Run the seeders** to populate your database
2. **Test the API endpoints** with realistic data
3. **Customize the products** for your specific needs
4. **Add more merchants** and products as required

The system now has a complete, realistic dataset that demonstrates all the product management features with proper relationships and multilingual support.
