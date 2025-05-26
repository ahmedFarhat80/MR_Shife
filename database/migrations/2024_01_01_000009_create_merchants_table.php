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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();

            // Basic Information (Step 1)
            $table->json('name'); // {en: "John Doe", ar: "جون دو"}
            $table->string('phone_number')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('is_phone_verified')->default(false);

            // Subscription Information (Step 2)
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->enum('subscription_status', ['pending', 'active', 'cancelled', 'expired'])->default('pending');
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->decimal('subscription_amount', 10, 2)->nullable();
            $table->string('payment_method')->nullable(); // 'card', 'paypal', etc.
            $table->json('payment_details')->nullable(); // Store payment info
            $table->boolean('is_subscription_paid')->default(false);

            // Business Information (Step 4)
            $table->json('business_name')->nullable(); // {en: "My Restaurant", ar: "مطعمي"}
            $table->json('business_address')->nullable(); // {en: "123 Main St", ar: "شارع الرئيسي 123"}
            $table->string('business_type')->nullable(); // restaurant, cafe, grocery, etc.
            $table->string('commercial_registration_number')->nullable();
            $table->string('work_permit')->nullable(); // File path
            $table->string('id_or_passport')->nullable(); // File path
            $table->string('health_certificate')->nullable(); // File path

            // Business Profile (Step 4)
            $table->string('business_logo')->nullable(); // File path
            $table->json('business_description')->nullable(); // {en: "Best food in town", ar: "أفضل طعام في المدينة"}
            $table->json('business_hours')->nullable(); // Operating hours
            $table->string('business_phone')->nullable(); // Business contact number
            $table->string('business_email')->nullable(); // Business email
            $table->json('social_media')->nullable(); // {facebook: "", instagram: "", twitter: ""}

            // Location Information (Step 5)
            $table->decimal('location_latitude', 10, 7)->nullable();
            $table->decimal('location_longitude', 10, 7)->nullable();
            $table->json('location_address')->nullable(); // Full address with city, area
            $table->string('location_city')->nullable();
            $table->string('location_area')->nullable();
            $table->string('location_building')->nullable();
            $table->string('location_floor')->nullable();
            $table->text('location_notes')->nullable(); // Additional location notes

            // System Fields
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->enum('registration_step', ['basic_info', 'phone_verification', 'subscription', 'business_info', 'business_profile', 'location', 'completed'])->default('basic_info');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->string('preferred_language', 2)->default('ar'); // 'ar' or 'en'
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('settings')->nullable(); // Store various settings

            // Laravel Auth Fields
            $table->rememberToken();
            $table->timestamps();

            // Indexes
            $table->index('phone_number');
            $table->index('email');
            $table->index('registration_step');
            $table->index('subscription_status');
            $table->index('location_city');
            $table->index('location_area');

            // Foreign Keys
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
