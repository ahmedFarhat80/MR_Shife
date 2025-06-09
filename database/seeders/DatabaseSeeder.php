<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed all data in correct order
        $this->call([
            // Core data first
            SubscriptionPlanSeeder::class,
            FoodNationalitiesSeeder::class,

            // Create realistic merchants and customers
            RealisticMerchantsSeeder::class,
            RealisticCustomersSeeder::class,

            // Create internal categories for each merchant
            RealisticInternalCategoriesSeeder::class,

            // Create realistic products for each merchant type
            RealisticRestaurantProductsSeeder::class,
            RealisticCafeProductsSeeder::class,
        ]);
    }
}
