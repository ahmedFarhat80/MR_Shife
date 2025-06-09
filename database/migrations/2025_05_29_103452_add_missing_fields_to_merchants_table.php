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
        Schema::table('merchants', function (Blueprint $table) {
            // Add missing fields for merchant details
            $table->decimal('average_rating', 3, 2)->default(0.00)->after('is_featured');
            $table->integer('reviews_count')->default(0)->after('average_rating');
            $table->integer('orders_count')->default(0)->after('reviews_count');
            $table->decimal('delivery_fee', 8, 2)->default(0.00)->after('orders_count');
            $table->decimal('minimum_order', 8, 2)->default(0.00)->after('delivery_fee');
            $table->integer('delivery_radius')->default(10)->after('minimum_order'); // in km
            $table->string('location_postal_code')->nullable()->after('location_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'average_rating',
                'reviews_count',
                'orders_count',
                'delivery_fee',
                'minimum_order',
                'delivery_radius',
                'location_postal_code'
            ]);
        });
    }
};
