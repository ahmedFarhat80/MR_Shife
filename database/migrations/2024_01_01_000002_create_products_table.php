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
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->json('name'); // Translatable: {en: "Chicken Burger", ar: "برجر الدجاج"}
            $table->json('description')->nullable(); // Translatable description
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->string('sku')->unique()->nullable();
            $table->json('images')->nullable(); // Array of image URLs
            $table->integer('preparation_time')->default(15); // in minutes
            $table->integer('calories')->nullable();
            $table->json('ingredients')->nullable(); // Translatable array
            $table->json('allergens')->nullable(); // Translatable array
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_spicy')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->boolean('track_stock')->default(false);
            $table->timestamps();
            
            $table->index(['merchant_id', 'is_available']);
            $table->index(['category_id', 'is_available']);
            $table->index('is_featured');
            $table->index('sort_order');
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
