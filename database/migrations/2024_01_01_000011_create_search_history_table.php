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
        Schema::create('search_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            
            // Search Information
            $table->string('query'); // The search term
            $table->string('search_type')->default('general'); // 'general', 'products', 'restaurants', 'autocomplete'
            $table->json('filters')->nullable(); // Applied filters: category, price range, etc.
            $table->integer('results_count')->default(0); // Number of results returned
            
            // Context Information
            $table->string('language', 2)->default('en'); // User's language when searching
            $table->string('user_agent')->nullable(); // Device/browser info
            $table->ipAddress('ip_address')->nullable(); // User's IP
            
            // Location Context (if available)
            $table->decimal('user_latitude', 10, 7)->nullable();
            $table->decimal('user_longitude', 10, 7)->nullable();
            
            // Interaction Data
            $table->boolean('clicked_result')->default(false); // Did user click on any result
            $table->string('clicked_result_type')->nullable(); // 'product', 'restaurant'
            $table->unsignedBigInteger('clicked_result_id')->nullable(); // ID of clicked item
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['customer_id', 'created_at']);
            $table->index(['query', 'language']);
            $table->index(['search_type', 'created_at']);
            $table->index('results_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
