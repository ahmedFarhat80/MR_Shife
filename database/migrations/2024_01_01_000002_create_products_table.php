<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('internal_category_id')->nullable()->constrained('internal_categories')->onDelete('set null');
            $table->foreignId('food_nationality_id')->nullable()->constrained('food_nationalities')->onDelete('set null');

            // Basic Information
            $table->json('name'); // Translatable: {en: "Chicken Burger", ar: "برجر الدجاج"}
            $table->json('description')->nullable(); // Translatable description

            // Background/Cover
            $table->enum('background_type', ['color', 'image'])->default('color');
            $table->string('background_value')->nullable(); // hex color or image path

            // Pricing
            $table->decimal('base_price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->nullable(); // 0.00 to 100.00
            $table->decimal('discounted_price', 10, 2)->nullable(); // calculated field

            // Availability & Timing
            $table->boolean('is_available')->default(true);
            $table->integer('preparation_time')->default(15); // in minutes

            // Additional Fields (keeping for compatibility)
            $table->string('sku')->unique()->nullable();
            $table->integer('calories')->nullable();
            $table->json('ingredients')->nullable(); // Translatable array
            $table->json('allergens')->nullable(); // Translatable array
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_spicy')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_stock')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['merchant_id', 'is_available']);
            $table->index(['internal_category_id', 'is_available']);
            $table->index(['food_nationality_id', 'is_available']);
            $table->index('is_featured');
            $table->index('sort_order');
            $table->index('base_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
