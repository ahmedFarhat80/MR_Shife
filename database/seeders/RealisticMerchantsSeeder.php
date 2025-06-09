<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Merchant;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class RealisticMerchantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get subscription plans
        $basicPlan = SubscriptionPlan::first();
        $premiumPlan = SubscriptionPlan::skip(1)->first();

        if (!$basicPlan || !$premiumPlan) {
            $this->command->warn('Subscription plans not found. Please run SubscriptionPlanSeeder first.');
            return;
        }

        $merchants = [
            // Restaurant Merchant
            [
                'name' => [
                    'en' => 'Al Salam Restaurant',
                    'ar' => 'مطعم السلام'
                ],
                'phone_number' => '+966501234567',
                'email' => 'info@alsalam-restaurant.com',
                'is_phone_verified' => true,
                'phone_verified_at' => Carbon::now()->subDays(30),
                'preferred_language' => 'ar',

                // Subscription Information
                'subscription_plan_id' => $premiumPlan->id,
                'subscription_status' => 'active',
                'subscription_starts_at' => Carbon::now()->subDays(30),
                'subscription_ends_at' => Carbon::now()->addDays(335),
                'subscription_amount' => $premiumPlan->price,
                'payment_method' => 'card',
                'payment_details' => [
                    'card_last_four' => '1234',
                    'card_brand' => 'visa'
                ],
                'is_subscription_paid' => true,

                // Business Information
                'business_name' => [
                    'en' => 'Al Salam Traditional Restaurant',
                    'ar' => 'مطعم السلام للأكلات الشعبية'
                ],
                'business_address' => [
                    'en' => 'King Fahd Road, Al Olaya District, Riyadh',
                    'ar' => 'طريق الملك فهد، حي العليا، الرياض'
                ],
                'business_type' => 'restaurant',
                'commercial_registration_number' => '1010123456',
                'work_permit' => 'documents/work_permit_1.pdf',
                'id_or_passport' => 'documents/id_1.pdf',
                'health_certificate' => 'documents/health_cert_1.pdf',

                // Business Profile
                'business_logo' => 'merchant_logos/alsalam_logo.png',
                'business_description' => [
                    'en' => 'Authentic Saudi and Middle Eastern cuisine served in a traditional atmosphere. We specialize in grilled meats, traditional rice dishes, and fresh salads.',
                    'ar' => 'مأكولات سعودية وشرق أوسطية أصيلة تُقدم في أجواء تراثية. نتخصص في اللحوم المشوية والأرز التقليدي والسلطات الطازجة.'
                ],
                'business_hours' => [
                    'saturday' => ['open' => '11:00', 'close' => '23:00'],
                    'sunday' => ['open' => '11:00', 'close' => '23:00'],
                    'monday' => ['open' => '11:00', 'close' => '23:00'],
                    'tuesday' => ['open' => '11:00', 'close' => '23:00'],
                    'wednesday' => ['open' => '11:00', 'close' => '23:00'],
                    'thursday' => ['open' => '11:00', 'close' => '23:00'],
                    'friday' => ['open' => '14:00', 'close' => '23:00']
                ],
                'business_phone' => '+966112345678',
                'business_email' => 'orders@alsalam-restaurant.com',
                'social_media' => [
                    'instagram' => '@alsalam_restaurant',
                    'twitter' => '@alsalam_rest',
                    'facebook' => 'AlSalamRestaurant'
                ],

                // Location Information
                'location_latitude' => 24.7136,
                'location_longitude' => 46.6753,
                'location_address' => [
                    'en' => '123 King Fahd Road, Al Olaya District',
                    'ar' => '123 طريق الملك فهد، حي العليا'
                ],
                'location_city' => 'Riyadh',
                'location_area' => 'Al Olaya',

                // System Fields
                'status' => 'active',
                'registration_step' => 'completed',
                'is_verified' => true,
                'is_approved' => true,
                'approved_at' => Carbon::now()->subDays(25),
                'completed_at' => Carbon::now()->subDays(25),
            ],

            // Cafe Merchant
            [
                'name' => [
                    'en' => 'Coffee Corner Cafe',
                    'ar' => 'مقهى ركن القهوة'
                ],
                'phone_number' => '+966507654321',
                'email' => 'hello@coffeecorner.sa',
                'is_phone_verified' => true,
                'phone_verified_at' => Carbon::now()->subDays(20),
                'preferred_language' => 'en',

                // Subscription Information
                'subscription_plan_id' => $basicPlan->id,
                'subscription_status' => 'active',
                'subscription_starts_at' => Carbon::now()->subDays(20),
                'subscription_ends_at' => Carbon::now()->addDays(345),
                'subscription_amount' => $basicPlan->price,
                'payment_method' => 'card',
                'payment_details' => [
                    'card_last_four' => '5678',
                    'card_brand' => 'mastercard'
                ],
                'is_subscription_paid' => true,

                // Business Information
                'business_name' => [
                    'en' => 'Coffee Corner Specialty Cafe',
                    'ar' => 'مقهى ركن القهوة المتخصص'
                ],
                'business_address' => [
                    'en' => 'Prince Mohammed Bin Abdulaziz Road, Al Malaz, Riyadh',
                    'ar' => 'طريق الأمير محمد بن عبدالعزيز، حي الملز، الرياض'
                ],
                'business_type' => 'cafe',
                'commercial_registration_number' => '1010654321',
                'work_permit' => 'documents/work_permit_2.pdf',
                'id_or_passport' => 'documents/id_2.pdf',
                'health_certificate' => 'documents/health_cert_2.pdf',

                // Business Profile
                'business_logo' => 'merchant_logos/coffeecorner_logo.png',
                'business_description' => [
                    'en' => 'Modern specialty coffee shop serving premium coffee, fresh pastries, and light meals. Perfect for work meetings, studying, or casual hangouts.',
                    'ar' => 'مقهى حديث متخصص في القهوة المميزة والمعجنات الطازجة والوجبات الخفيفة. مثالي لاجتماعات العمل والدراسة أو اللقاءات العادية.'
                ],
                'business_hours' => [
                    'saturday' => ['open' => '06:00', 'close' => '22:00'],
                    'sunday' => ['open' => '06:00', 'close' => '22:00'],
                    'monday' => ['open' => '06:00', 'close' => '22:00'],
                    'tuesday' => ['open' => '06:00', 'close' => '22:00'],
                    'wednesday' => ['open' => '06:00', 'close' => '22:00'],
                    'thursday' => ['open' => '06:00', 'close' => '22:00'],
                    'friday' => ['open' => '08:00', 'close' => '22:00']
                ],
                'business_phone' => '+966118765432',
                'business_email' => 'orders@coffeecorner.sa',
                'social_media' => [
                    'instagram' => '@coffee_corner_sa',
                    'twitter' => '@coffeecorner_sa',
                    'facebook' => 'CoffeeCornerSA'
                ],

                // Location Information
                'location_latitude' => 24.6877,
                'location_longitude' => 46.7219,
                'location_address' => [
                    'en' => '456 Prince Mohammed Bin Abdulaziz Road, Al Malaz',
                    'ar' => '456 طريق الأمير محمد بن عبدالعزيز، حي الملز'
                ],
                'location_city' => 'Riyadh',
                'location_area' => 'Al Malaz',

                // System Fields
                'status' => 'active',
                'registration_step' => 'completed',
                'is_verified' => true,
                'is_approved' => true,
                'approved_at' => Carbon::now()->subDays(15),
                'completed_at' => Carbon::now()->subDays(15),
            ]
        ];

        foreach ($merchants as $merchantData) {
            Merchant::create($merchantData);
        }

        $this->command->info('Realistic merchants created successfully!');
    }
}
