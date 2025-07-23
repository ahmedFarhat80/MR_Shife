<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎭 Creating Roles...');

        // إنشاء الأدوار الافتراضية
        $roles = $this->getRoles();

        foreach ($roles as $roleName => $roleData) {
            $role = Role::updateOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => 'admin'
                ],
                [
                    'name' => $roleName,
                    'guard_name' => 'admin'
                ]
            );

            // إعطاء الصلاحيات للدور
            if ($roleName === 'super_admin') {
                // السوبر أدمن يحصل على جميع الصلاحيات
                $allPermissions = Permission::where('guard_name', 'admin')->get();
                $role->syncPermissions($allPermissions);
                $this->command->info("  ✅ {$roleName}: جميع الصلاحيات ({$allPermissions->count()})");
            } else {
                // الأدوار الأخرى تحصل على صلاحيات محددة
                $permissions = Permission::whereIn('name', $roleData['permissions'])
                    ->where('guard_name', 'admin')
                    ->get();
                $role->syncPermissions($permissions);
                $this->command->info("  ✅ {$roleName}: {$permissions->count()} صلاحية");
            }
        }

        $this->command->info("✅ Created " . count($roles) . " roles");
    }

    /**
     * الحصول على الأدوار الافتراضية مع صلاحياتها
     */
    private function getRoles(): array
    {
        return [
            'super_admin' => [
                'description' => 'سوبر أدمن - جميع الصلاحيات',
                'permissions' => [] // سيحصل على جميع الصلاحيات
            ],

            'مدير عام' => [
                'description' => 'مدير عام - صلاحيات إدارية شاملة',
                'permissions' => [
                    // إدارة المستخدمين
                    'view_any_admin', 'view_admin', 'create_admin', 'update_admin',
                    'view_any_customer', 'view_customer', 'update_customer',
                    'view_any_merchant', 'view_merchant', 'update_merchant',

                    // إدارة المحتوى
                    'view_any_product', 'view_product', 'create_product', 'update_product',
                    'view_any_category', 'view_category', 'create_category', 'update_category',
                    'view_any_internal::category', 'view_internal::category', 'create_internal::category', 'update_internal::category',

                    // إدارة الطلبات
                    'view_any_order', 'view_order', 'update_order',

                    // إدارة الأدوار
                    'view_any_role', 'view_role', 'create_role', 'update_role',

                    // الوسائط
                    'view_any_media', 'view_media', 'create_media', 'update_media',

                    // الويدجت
                    'widget_MainStatsWidget',
                    'widget_SystemHealthWidget',
                    'widget_BusinessMetricsWidget',
                    'widget_UserEngagementWidget',
                ]
            ],

            'مدير محتوى' => [
                'description' => 'مدير محتوى - إدارة المنتجات والفئات',
                'permissions' => [
                    // إدارة المنتجات
                    'view_any_product', 'view_product', 'create_product', 'update_product', 'delete_product',

                    // إدارة الفئات
                    'view_any_category', 'view_category', 'create_category', 'update_category', 'delete_category',
                    'view_any_internal::category', 'view_internal::category', 'create_internal::category', 'update_internal::category', 'delete_internal::category',

                    // عرض التجار
                    'view_any_merchant', 'view_merchant',

                    // الوسائط
                    'view_any_media', 'view_media', 'create_media', 'update_media', 'delete_media',

                    // ويدجت المحتوى
                    'widget_MainStatsWidget',
                ]
            ],

            'مدير عملاء' => [
                'description' => 'مدير عملاء - إدارة العملاء والطلبات',
                'permissions' => [
                    // إدارة العملاء
                    'view_any_customer', 'view_customer', 'update_customer',

                    // إدارة التجار
                    'view_any_merchant', 'view_merchant', 'update_merchant',

                    // إدارة الطلبات
                    'view_any_order', 'view_order', 'update_order',

                    // عرض المنتجات
                    'view_any_product', 'view_product',

                    // ويدجت العملاء
                    'widget_MainStatsWidget',
                    'widget_UserEngagementWidget',
                ]
            ],

            'مدير مالي' => [
                'description' => 'مدير مالي - إدارة الاشتراكات والمدفوعات',
                'permissions' => [
                    // إدارة الاشتراكات
                    'view_any_subscription::plan', 'view_subscription::plan', 'create_subscription::plan', 'update_subscription::plan',

                    // عرض التجار والعملاء
                    'view_any_merchant', 'view_merchant',
                    'view_any_customer', 'view_customer',

                    // عرض الطلبات
                    'view_any_order', 'view_order',

                    // ويدجت مالية
                    'widget_MainStatsWidget',
                    'widget_BusinessMetricsWidget',
                ]
            ],

            'مشرف' => [
                'description' => 'مشرف - صلاحيات عرض فقط',
                'permissions' => [
                    // عرض فقط
                    'view_any_admin', 'view_admin',
                    'view_any_customer', 'view_customer',
                    'view_any_merchant', 'view_merchant',
                    'view_any_product', 'view_product',
                    'view_any_category', 'view_category',
                    'view_any_order', 'view_order',
                    'view_any_role', 'view_role',

                    // ويدجت أساسية
                    'widget_MainStatsWidget',
                ]
            ],
        ];
    }
}
