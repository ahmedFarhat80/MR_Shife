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
            // Add missing columns for product features
            $table->boolean('is_popular')->default(false)->after('is_featured');
            $table->integer('total_orders')->default(0)->after('is_popular');
            $table->decimal('average_rating', 3, 2)->default(0.00)->after('total_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_popular', 'total_orders', 'average_rating']);
        });
    }
};
