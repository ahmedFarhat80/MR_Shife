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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Translatable: {en: "Premium Plan", ar: "الخطة المميزة"}
            $table->json('description')->nullable(); // Translatable description
            $table->decimal('price', 10, 2)->default(0); // Price in USD
            $table->enum('period', ['monthly', 'half_year', 'annual'])->default('monthly');
            $table->json('features'); // Array of features: {en: ["Feature 1"], ar: ["الميزة 1"]}
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false); // For highlighting popular plans
            $table->integer('sort_order')->default(0);
            $table->string('stripe_price_id')->nullable(); // For future Stripe integration
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
