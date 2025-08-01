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
            // 1. Permissions and roles first (MUST be first)
            PermissionSeeder::class,
            RoleSeeder::class,

            // 2. Admin users (after roles are created)
            AdminSeeder::class,

            // 3. Core data (subscription plans and food nationalities)
            SubscriptionPlanSeeder::class,
            FoodNationalitiesSeeder::class,

            // 4. Create realistic merchants and customers
            RealisticMerchantsSeeder::class,
            RealisticCustomersSeeder::class,

            // 5. Create internal categories for each merchant
            InternalCategoriesSeeder::class,

            // 6. Create complete products with options and sizes
            CompleteProductsSeeder::class, // Complete products with sizes and options in one go

            // 7. Create orders with realistic data
            OrderSeeder::class,
        ]);
    }
}
