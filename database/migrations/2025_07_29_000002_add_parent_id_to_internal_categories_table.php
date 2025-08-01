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
        Schema::table('internal_categories', function (Blueprint $table) {
            // Add parent_id for hierarchical categories (main -> sub)
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('internal_categories')->onDelete('cascade');
            
            // Add level field to distinguish between main (0) and sub (1) categories
            $table->tinyInteger('level')->default(0)->after('parent_id'); // 0 = main, 1 = sub
            
            // Add indexes for better performance
            $table->index(['parent_id', 'is_active']);
            $table->index(['level', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internal_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id', 'is_active']);
            $table->dropIndex(['level', 'is_active']);
            $table->dropColumn(['parent_id', 'level']);
        });
    }
};
