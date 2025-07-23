<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👤 Creating Admin Users...');

        // إنشاء السوبر أدمن
        $this->createSuperAdmin();

        // إنشاء مديرين تجريبيين
        $this->createSampleAdmins();

        $this->command->info('✅ Admin users created successfully');
    }

    /**
     * إنشاء السوبر أدمن
     */
    private function createSuperAdmin(): void
    {
        $superAdmin = Admin::updateOrCreate(
            ['email' => 'admin@mrshife.com'],
            [
                'name' => 'MR Shife Super Admin',
                'email' => 'admin@mrshife.com',
                'password' => Hash::make('password123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // تعيين دور السوبر أدمن
        $superAdminRole = Role::where('name', 'super_admin')
            ->where('guard_name', 'admin')
            ->first();

        if ($superAdminRole) {
            $superAdmin->syncRoles([$superAdminRole]);
            $this->command->info("  ✅ Super Admin: {$superAdmin->name} ({$superAdmin->email})");
            $this->command->info("     🔑 Password: password123");
            $this->command->info("     👑 Role: super_admin");
        }
    }

    /**
     * إنشاء مديرين تجريبيين
     */
    private function createSampleAdmins(): void
    {
        $sampleAdmins = [
            [
                'name' => 'أحمد محمد',
                'email' => 'manager@mrshife.com',
                'password' => 'password123',
                'role' => 'مدير عام'
            ],
            [
                'name' => 'فاطمة علي',
                'email' => 'content@mrshife.com',
                'password' => 'password123',
                'role' => 'مدير محتوى'
            ],
            [
                'name' => 'محمد حسن',
                'email' => 'customers@mrshife.com',
                'password' => 'password123',
                'role' => 'مدير عملاء'
            ],
            [
                'name' => 'سارة أحمد',
                'email' => 'finance@mrshife.com',
                'password' => 'password123',
                'role' => 'مدير مالي'
            ],
            [
                'name' => 'عمر خالد',
                'email' => 'supervisor@mrshife.com',
                'password' => 'password123',
                'role' => 'مشرف'
            ],
        ];

        foreach ($sampleAdmins as $adminData) {
            $admin = Admin::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'email' => $adminData['email'],
                    'password' => Hash::make($adminData['password']),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // تعيين الدور
            $role = Role::where('name', $adminData['role'])
                ->where('guard_name', 'admin')
                ->first();

            if ($role) {
                $admin->syncRoles([$role]);
                $this->command->info("  ✅ {$admin->name} ({$admin->email}) - Role: {$adminData['role']}");
            }
        }

        $this->command->info("  🔑 جميع كلمات المرور: password123");
    }
}
