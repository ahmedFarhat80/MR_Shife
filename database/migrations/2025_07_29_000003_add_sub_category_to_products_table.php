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
        Schema::table('products', function (Blueprint $table) {
            // Add sub_category_id for hierarchical categorization
            $table->foreignId('sub_category_id')->nullable()->after('internal_category_id')->constrained('internal_categories')->onDelete('set null');
            
            // Add index for better performance
            $table->index(['internal_category_id', 'sub_category_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sub_category_id']);
            $table->dropIndex(['internal_category_id', 'sub_category_id', 'is_available']);
            $table->dropColumn('sub_category_id');
        });
    }
};
