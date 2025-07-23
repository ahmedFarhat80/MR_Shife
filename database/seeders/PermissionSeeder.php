<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Creating Permissions...');

        // إنشاء جميع الصلاحيات المطلوبة
        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => 'admin'
                ],
                [
                    'name' => $permission,
                    'guard_name' => 'admin'
                ]
            );
        }

        $this->command->info("✅ Created " . count($permissions) . " permissions");
    }

    /**
     * الحصول على جميع الصلاحيات المطلوبة
     */
    private function getPermissions(): array
    {
        return [
            // Admin permissions
            'view_admin',
            'view_any_admin',
            'create_admin',
            'update_admin',
            'delete_admin',
            'delete_any_admin',
            'restore_admin',
            'restore_any_admin',
            'force_delete_admin',
            'force_delete_any_admin',

            // Customer permissions
            'view_customer',
            'view_any_customer',
            'create_customer',
            'update_customer',
            'delete_customer',
            'delete_any_customer',
            'restore_customer',
            'restore_any_customer',
            'force_delete_customer',
            'force_delete_any_customer',

            // Merchant permissions
            'view_merchant',
            'view_any_merchant',
            'create_merchant',
            'update_merchant',
            'delete_merchant',
            'delete_any_merchant',
            'restore_merchant',
            'restore_any_merchant',
            'force_delete_merchant',
            'force_delete_any_merchant',

            // Product permissions
            'view_product',
            'view_any_product',
            'create_product',
            'update_product',
            'delete_product',
            'delete_any_product',
            'restore_product',
            'restore_any_product',
            'force_delete_product',
            'force_delete_any_product',

            // Order permissions
            'view_order',
            'view_any_order',
            'create_order',
            'update_order',
            'delete_order',
            'delete_any_order',
            'restore_order',
            'restore_any_order',
            'force_delete_order',
            'force_delete_any_order',

            // Category permissions
            'view_category',
            'view_any_category',
            'create_category',
            'update_category',
            'delete_category',
            'delete_any_category',
            'restore_category',
            'restore_any_category',
            'force_delete_category',
            'force_delete_any_category',

            // Internal Category permissions
            'view_internal::category',
            'view_any_internal::category',
            'create_internal::category',
            'update_internal::category',
            'delete_internal::category',
            'delete_any_internal::category',
            'restore_internal::category',
            'restore_any_internal::category',
            'force_delete_internal::category',
            'force_delete_any_internal::category',

            // Subscription Plan permissions
            'view_subscription::plan',
            'view_any_subscription::plan',
            'create_subscription::plan',
            'update_subscription::plan',
            'delete_subscription::plan',
            'delete_any_subscription::plan',
            'restore_subscription::plan',
            'restore_any_subscription::plan',
            'force_delete_subscription::plan',
            'force_delete_any_subscription::plan',

            // Role permissions
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
            'restore_role',
            'restore_any_role',
            'force_delete_role',
            'force_delete_any_role',

            // Media permissions - Temporarily disabled
            // Uncomment when Media Library is needed
            // 'view_media',
            // 'view_any_media',
            // 'create_media',
            // 'update_media',
            // 'delete_media',
            // 'delete_any_media',
            // 'restore_media',
            // 'restore_any_media',
            // 'force_delete_media',
            // 'force_delete_any_media',

            // Widget permissions
            'widget_MainStatsWidget',
            'widget_SystemHealthWidget',
            'widget_BusinessMetricsWidget',
            'widget_UserEngagementWidget',
            'widget_PerformanceChartsWidget',
            'widget_RecentActivityWidget',
            'widget_ChartsRowWidget',

            // Page permissions
            'page_LanguageSwitch',
        ];
    }
}
