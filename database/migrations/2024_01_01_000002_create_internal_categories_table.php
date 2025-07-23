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
        Schema::create('internal_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Translatable: {en: "Appetizers", ar: "المقبلات"}
            $table->json('description')->nullable(); // Translatable description
            $table->string('image')->nullable(); // Category image
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_categories');
    }
};
