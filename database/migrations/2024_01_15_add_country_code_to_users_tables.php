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
        // Add country_code to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('country_code', 10)->default('+966')->after('phone_number');
        });

        // Add country_code to merchants table
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('country_code', 10)->default('+966')->after('phone_number');
        });

        // Update existing data to separate country code from phone number
        DB::statement("
            UPDATE customers 
            SET 
                country_code = '+966',
                phone_number = CASE 
                    WHEN phone_number LIKE '+966%' THEN SUBSTRING(phone_number, 5)
                    WHEN phone_number LIKE '966%' THEN SUBSTRING(phone_number, 4)
                    WHEN phone_number LIKE '0%' THEN SUBSTRING(phone_number, 2)
                    ELSE phone_number
                END
            WHERE phone_number IS NOT NULL
        ");

        DB::statement("
            UPDATE merchants 
            SET 
                country_code = '+966',
                phone_number = CASE 
                    WHEN phone_number LIKE '+966%' THEN SUBSTRING(phone_number, 5)
                    WHEN phone_number LIKE '966%' THEN SUBSTRING(phone_number, 4)
                    WHEN phone_number LIKE '0%' THEN SUBSTRING(phone_number, 2)
                    ELSE phone_number
                END
            WHERE phone_number IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original phone number format before dropping column
        DB::statement("
            UPDATE customers 
            SET phone_number = CONCAT(country_code, phone_number)
            WHERE country_code IS NOT NULL AND phone_number IS NOT NULL
        ");

        DB::statement("
            UPDATE merchants 
            SET phone_number = CONCAT(country_code, phone_number)
            WHERE country_code IS NOT NULL AND phone_number IS NOT NULL
        ");

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('country_code');
        });
    }
};
