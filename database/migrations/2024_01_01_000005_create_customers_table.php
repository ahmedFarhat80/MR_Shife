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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Basic Information
            $table->json('name'); // Translatable: {en: "John Doe", ar: "جون دو"}
            $table->string('phone_number')->unique();
            $table->string('email')->nullable()->unique();
            $table->enum('preferred_language', ['ar', 'en'])->default('ar');

            // Verification Status
            $table->boolean('phone_verified')->default(false);
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();

            // Profile Information
            $table->string('avatar')->nullable(); // Profile picture path
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            // Address Information
            $table->json('addresses')->nullable(); // Multiple addresses as JSON
            $table->json('default_address')->nullable(); // Default delivery address

            // Preferences
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('sms_notifications')->default(true);
            $table->boolean('email_notifications')->default(true);

            // Account Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();

            // Loyalty & Points (calculated dynamically from orders)
            $table->integer('loyalty_points')->default(0);

            // System Fields
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['phone_number', 'phone_verified']);
            $table->index(['email', 'email_verified']);
            $table->index(['status', 'created_at']);
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
