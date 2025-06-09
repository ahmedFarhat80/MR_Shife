<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Carbon\Carbon;

class RealisticCustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            // Customer 1 - Arabic Preference
            [
                'name' => [
                    'en' => 'Ahmed Al-Rashid',
                    'ar' => 'أحمد الراشد'
                ],
                'phone_number' => '+966501111111',
                'email' => 'ahmed.rashid@gmail.com',
                'preferred_language' => 'ar',

                // Verification Status
                'phone_verified' => true,
                'phone_verified_at' => Carbon::now()->subDays(45),
                'email_verified' => true,
                'email_verified_at' => Carbon::now()->subDays(44),

                // Personal Information
                'date_of_birth' => '1985-03-15',
                'gender' => 'male',
                'avatar' => 'user_avatars/ahmed_avatar.jpg',

                // Addresses
                'addresses' => [
                    [
                        'address_line' => 'Building 123, King Abdulaziz Road',
                        'address_line_ar' => 'مبنى 123، طريق الملك عبدالعزيز',
                        'city' => 'Riyadh',
                        'city_ar' => 'الرياض',
                        'area' => 'Al Malaz',
                        'area_ar' => 'الملز',
                        'building' => 'Building 123',
                        'building_ar' => 'مبنى 123',
                        'floor' => '3rd Floor',
                        'floor_ar' => 'الطابق الثالث',
                        'apartment' => 'Apt 301',
                        'apartment_ar' => 'شقة 301',
                        'latitude' => 24.6877,
                        'longitude' => 46.7219,
                        'notes' => 'Near the main entrance, blue building',
                        'notes_ar' => 'بالقرب من المدخل الرئيسي، المبنى الأزرق',
                        'is_default' => true
                    ],
                    [
                        'address_line' => 'Office Tower, King Fahd Road',
                        'address_line_ar' => 'برج المكاتب، طريق الملك فهد',
                        'city' => 'Riyadh',
                        'city_ar' => 'الرياض',
                        'area' => 'Al Olaya',
                        'area_ar' => 'العليا',
                        'building' => 'Office Tower',
                        'building_ar' => 'برج المكاتب',
                        'floor' => '15th Floor',
                        'floor_ar' => 'الطابق الخامس عشر',
                        'apartment' => 'Office 1502',
                        'apartment_ar' => 'مكتب 1502',
                        'latitude' => 24.7136,
                        'longitude' => 46.6753,
                        'notes' => 'Work address - available 9 AM to 6 PM',
                        'notes_ar' => 'عنوان العمل - متاح من 9 صباحاً إلى 6 مساءً',
                        'is_default' => false
                    ]
                ],

                // Default Address
                'default_address' => [
                    'address_line' => 'Building 123, King Abdulaziz Road',
                    'address_line_ar' => 'مبنى 123، طريق الملك عبدالعزيز',
                    'city' => 'Riyadh',
                    'city_ar' => 'الرياض',
                    'area' => 'Al Malaz',
                    'area_ar' => 'الملز',
                    'building' => 'Building 123',
                    'building_ar' => 'مبنى 123',
                    'floor' => '3rd Floor',
                    'floor_ar' => 'الطابق الثالث',
                    'apartment' => 'Apt 301',
                    'apartment_ar' => 'شقة 301',
                    'latitude' => 24.6877,
                    'longitude' => 46.7219,
                    'notes' => 'Near the main entrance, blue building',
                    'notes_ar' => 'بالقرب من المدخل الرئيسي، المبنى الأزرق'
                ],

                // Notification Preferences
                'notifications_enabled' => true,
                'sms_notifications' => true,
                'email_notifications' => true,
                'push_notifications' => true,

                // Loyalty & Points
                'loyalty_points' => 150,

                // System Fields
                'status' => 'active',
                'last_login_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subDays(45),
                'updated_at' => Carbon::now()->subHours(2),
            ],

            // Customer 2 - English Preference
            [
                'name' => [
                    'en' => 'Sarah Johnson',
                    'ar' => 'سارة جونسون'
                ],
                'phone_number' => '+966502222222',
                'email' => 'sarah.johnson@outlook.com',
                'preferred_language' => 'en',

                // Verification Status
                'phone_verified' => true,
                'phone_verified_at' => Carbon::now()->subDays(30),
                'email_verified' => true,
                'email_verified_at' => Carbon::now()->subDays(29),

                // Personal Information
                'date_of_birth' => '1992-07-22',
                'gender' => 'female',
                'avatar' => 'user_avatars/sarah_avatar.jpg',

                // Addresses
                'addresses' => [
                    [
                        'address_line' => 'Villa 456, Al Nakheel District',
                        'address_line_ar' => 'فيلا 456، حي النخيل',
                        'city' => 'Riyadh',
                        'city_ar' => 'الرياض',
                        'area' => 'Al Nakheel',
                        'area_ar' => 'النخيل',
                        'building' => 'Villa 456',
                        'building_ar' => 'فيلا 456',
                        'floor' => 'Ground Floor',
                        'floor_ar' => 'الطابق الأرضي',
                        'apartment' => 'Main Villa',
                        'apartment_ar' => 'الفيلا الرئيسية',
                        'latitude' => 24.7500,
                        'longitude' => 46.6200,
                        'notes' => 'White villa with green gate, ring the bell twice',
                        'notes_ar' => 'فيلا بيضاء مع بوابة خضراء، اضغط الجرس مرتين',
                        'is_default' => true
                    ]
                ],

                // Default Address
                'default_address' => [
                    'address_line' => 'Villa 456, Al Nakheel District',
                    'address_line_ar' => 'فيلا 456، حي النخيل',
                    'city' => 'Riyadh',
                    'city_ar' => 'الرياض',
                    'area' => 'Al Nakheel',
                    'area_ar' => 'النخيل',
                    'building' => 'Villa 456',
                    'building_ar' => 'فيلا 456',
                    'floor' => 'Ground Floor',
                    'floor_ar' => 'الطابق الأرضي',
                    'apartment' => 'Main Villa',
                    'apartment_ar' => 'الفيلا الرئيسية',
                    'latitude' => 24.7500,
                    'longitude' => 46.6200,
                    'notes' => 'White villa with green gate, ring the bell twice',
                    'notes_ar' => 'فيلا بيضاء مع بوابة خضراء، اضغط الجرس مرتين'
                ],

                // Notification Preferences
                'notifications_enabled' => true,
                'sms_notifications' => false,
                'email_notifications' => true,
                'push_notifications' => true,

                // Loyalty & Points
                'loyalty_points' => 75,

                // System Fields
                'status' => 'active',
                'last_login_at' => Carbon::now()->subDays(1),
                'created_at' => Carbon::now()->subDays(30),
                'updated_at' => Carbon::now()->subDays(1),
            ]
        ];

        foreach ($customers as $customerData) {
            Customer::create($customerData);
        }

        $this->command->info('Realistic customers created successfully!');
    }
}
