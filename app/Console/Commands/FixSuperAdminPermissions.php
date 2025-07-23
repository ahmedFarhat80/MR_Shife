<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class FixSuperAdminPermissions extends Command
{
    protected $signature = 'admin:fix-super-admin';
    protected $description = 'Fix super admin permissions and ensure proper access';

    public function handle()
    {
        $this->info('🔧 Fixing Super Admin Permissions...');
        $this->newLine();

        // Step 1: Create or update super admin user
        $this->createSuperAdmin();

        // Step 2: Create super admin role
        $this->createSuperAdminRole();

        // Step 3: Create all necessary permissions
        $this->createPermissions();

        // Step 4: Assign all permissions to super admin role
        $this->assignPermissionsToSuperAdminRole();

        // Step 5: Assign super admin role to user
        $this->assignSuperAdminRole();

        $this->newLine();
        $this->info('✅ Super Admin permissions fixed successfully!');
        $this->info('🔑 You can now login with: admin@mrshife.com / password123');
    }

    private function createSuperAdmin()
    {
        $this->info('👤 Creating/Updating Super Admin User...');

        $admin = Admin::updateOrCreate(
            ['email' => 'admin@mrshife.com'],
            [
                'name' => 'MR Shife Super Admin',
                'email' => 'admin@mrshife.com',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // تأكد من أن المستخدم نشط ومتحقق منه
        $admin->update([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->line("  ✅ Super Admin: {$admin->name} ({$admin->email})");
    }

    private function createSuperAdminRole()
    {
        $this->info('🎭 Creating Super Admin Role...');

        $role = Role::updateOrCreate(
            [
                'name' => 'super_admin',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'super_admin',
                'guard_name' => 'admin'
            ]
        );

        $this->line("  ✅ Role: {$role->name}");
    }

    private function createPermissions()
    {
        $this->info('🔐 Creating All Permissions...');

        $permissions = [
            // Admin permissions
            'view_admin',
            'view_any_admin',
            'create_admin',
            'update_admin',
            'delete_admin',
            'delete_any_admin',

            // Customer permissions
            'view_customer',
            'view_any_customer',
            'create_customer',
            'update_customer',
            'delete_customer',
            'delete_any_customer',

            // Merchant permissions
            'view_merchant',
            'view_any_merchant',
            'create_merchant',
            'update_merchant',
            'delete_merchant',
            'delete_any_merchant',

            // Product permissions
            'view_product',
            'view_any_product',
            'create_product',
            'update_product',
            'delete_product',
            'delete_any_product',

            // Order permissions
            'view_order',
            'view_any_order',
            'create_order',
            'update_order',
            'delete_order',
            'delete_any_order',

            // Category permissions
            'view_category',
            'view_any_category',
            'create_category',
            'update_category',
            'delete_category',
            'delete_any_category',

            // Internal Category permissions
            'view_internal::category',
            'view_any_internal::category',
            'create_internal::category',
            'update_internal::category',
            'delete_internal::category',
            'delete_any_internal::category',

            // Subscription Plan permissions
            'view_subscription::plan',
            'view_any_subscription::plan',
            'create_subscription::plan',
            'update_subscription::plan',
            'delete_subscription::plan',
            'delete_any_subscription::plan',

            // Role permissions
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',

            // Food Nationality permissions
            'view_food::nationality',
            'view_any_food::nationality',
            'create_food::nationality',
            'update_food::nationality',
            'delete_food::nationality',
            'delete_any_food::nationality',
        ];

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

        $this->line("  ✅ Created " . count($permissions) . " permissions");
    }

    private function assignPermissionsToSuperAdminRole()
    {
        $this->info('🔗 Assigning All Permissions to Super Admin Role...');

        $role = Role::where('name', 'super_admin')->where('guard_name', 'admin')->first();
        $permissions = Permission::where('guard_name', 'admin')->get();

        $role->syncPermissions($permissions);

        $this->line("  ✅ Assigned {$permissions->count()} permissions to super_admin role");
    }

    private function assignSuperAdminRole()
    {
        $this->info('👑 Assigning Super Admin Role to User...');

        $admin = Admin::where('email', 'admin@mrshife.com')->first();
        $role = Role::where('name', 'super_admin')->where('guard_name', 'admin')->first();

        $admin->syncRoles([$role]);

        $this->line("  ✅ Assigned super_admin role to {$admin->name}");
    }
}
