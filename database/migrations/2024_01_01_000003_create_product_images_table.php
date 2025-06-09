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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path'); // Path to the image file
            $table->json('alt_text')->nullable(); // Translatable alt text
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false); // Main product image
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'is_primary']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
