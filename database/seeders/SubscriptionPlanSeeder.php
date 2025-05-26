<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing plans
        SubscriptionPlan::truncate();

        // Free Plan
        SubscriptionPlan::create([
            'name' => [
                'en' => 'Free Plan',
                'ar' => 'الخطة المجانية'
            ],
            'description' => [
                'en' => 'Perfect for getting started with basic features',
                'ar' => 'مثالية للبدء مع الميزات الأساسية'
            ],
            'price' => 0.00,
            'period' => 'monthly',
            'features' => [
                'en' => [
                    'Basic restaurant management',
                    'Up to 10 menu items',
                    'Basic order tracking',
                    'Email support'
                ],
                'ar' => [
                    'إدارة أساسية للمطعم',
                    'حتى 10 عناصر في القائمة',
                    'تتبع أساسي للطلبات',
                    'دعم عبر البريد الإلكتروني'
                ]
            ],
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 1,
        ]);

        // Premium Monthly Plan
        SubscriptionPlan::create([
            'name' => [
                'en' => 'Premium Monthly',
                'ar' => 'الخطة المميزة الشهرية'
            ],
            'description' => [
                'en' => 'Full access to all premium features with monthly billing',
                'ar' => 'وصول كامل لجميع الميزات المميزة مع الفوترة الشهرية'
            ],
            'price' => 7.99,
            'period' => 'monthly',
            'features' => [
                'en' => [
                    'Unlimited menu items',
                    'Advanced analytics and reports',
                    'Real-time order notifications',
                    'Customer management system',
                    'Inventory tracking',
                    'Multi-location support',
                    'Priority customer support',
                    'Custom branding options'
                ],
                'ar' => [
                    'عناصر قائمة غير محدودة',
                    'تحليلات وتقارير متقدمة',
                    'إشعارات الطلبات في الوقت الفعلي',
                    'نظام إدارة العملاء',
                    'تتبع المخزون',
                    'دعم متعدد المواقع',
                    'دعم عملاء ذو أولوية',
                    'خيارات العلامة التجارية المخصصة'
                ]
            ],
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 2,
        ]);

        // Premium Half Year Plan
        SubscriptionPlan::create([
            'name' => [
                'en' => 'Premium Half Year',
                'ar' => 'الخطة المميزة نصف السنوية'
            ],
            'description' => [
                'en' => 'Save 25% with our 6-month premium plan',
                'ar' => 'وفر 25% مع خطتنا المميزة لمدة 6 أشهر'
            ],
            'price' => 35.99,
            'period' => 'half_year',
            'features' => [
                'en' => [
                    'All Premium Monthly features',
                    'Advanced reporting dashboard',
                    'API access for integrations',
                    'Bulk operations support',
                    'Advanced customer segmentation',
                    'Marketing automation tools',
                    'Dedicated account manager',
                    '25% savings compared to monthly'
                ],
                'ar' => [
                    'جميع ميزات الخطة الشهرية المميزة',
                    'لوحة تقارير متقدمة',
                    'وصول API للتكاملات',
                    'دعم العمليات المجمعة',
                    'تقسيم متقدم للعملاء',
                    'أدوات التسويق الآلي',
                    'مدير حساب مخصص',
                    'توفير 25% مقارنة بالخطة الشهرية'
                ]
            ],
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 3,
        ]);

        // Premium Annual Plan
        SubscriptionPlan::create([
            'name' => [
                'en' => 'Premium Annual',
                'ar' => 'الخطة المميزة السنوية'
            ],
            'description' => [
                'en' => 'Best value! Save 50% with our annual premium plan',
                'ar' => 'أفضل قيمة! وفر 50% مع خطتنا المميزة السنوية'
            ],
            'price' => 79.99,
            'period' => 'annual',
            'features' => [
                'en' => [
                    'All Premium features included',
                    'Enterprise-grade security',
                    'Advanced analytics & insights',
                    'White-label solutions',
                    'Custom integrations',
                    'Training and onboarding',
                    '24/7 premium support',
                    'Early access to new features',
                    '50% savings compared to monthly'
                ],
                'ar' => [
                    'جميع الميزات المميزة مشمولة',
                    'أمان على مستوى المؤسسات',
                    'تحليلات ورؤى متقدمة',
                    'حلول العلامة البيضاء',
                    'تكاملات مخصصة',
                    'التدريب والإعداد',
                    'دعم مميز على مدار الساعة',
                    'وصول مبكر للميزات الجديدة',
                    'توفير 50% مقارنة بالخطة الشهرية'
                ]
            ],
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 4,
        ]);
    }
}
