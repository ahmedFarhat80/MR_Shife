<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:permissions 
                            {--fresh : Run with fresh migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup permissions and roles automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Setting up MR Shife Admin Panel...');
        $this->newLine();

        if ($this->option('fresh')) {
            $this->runFreshSetup();
        } else {
            $this->runNormalSetup();
        }

        $this->displayLoginInfo();
        return 0;
    }

    /**
     * تشغيل الإعداد مع fresh migration
     */
    private function runFreshSetup(): void
    {
        $this->info('🔄 Running fresh migration with seeding...');
        
        if (!$this->confirm('⚠️  هذا سيحذف جميع البيانات الموجودة. هل تريد المتابعة؟')) {
            $this->info('تم إلغاء العملية.');
            return;
        }

        // تشغيل fresh migration مع seeding
        $this->call('migrate:fresh', ['--seed' => true]);
        
        $this->info('✅ Fresh setup completed!');
    }

    /**
     * تشغيل الإعداد العادي
     */
    private function runNormalSetup(): void
    {
        $this->info('🔄 Running normal setup...');

        // تشغيل migrations
        $this->call('migrate');

        // إنشاء الصلاحيات
        $this->call('shield:generate', ['--all' => true]);

        // تشغيل seeders
        $this->call('db:seed', ['--class' => 'PermissionSeeder']);
        $this->call('db:seed', ['--class' => 'RoleSeeder']);
        $this->call('db:seed', ['--class' => 'AdminSeeder']);

        // تحديث الكاش
        $this->call('cache:clear');
        
        $this->info('✅ Normal setup completed!');
    }

    /**
     * عرض معلومات تسجيل الدخول
     */
    private function displayLoginInfo(): void
    {
        $this->newLine();
        $this->info('🎉 تم إعداد النظام بنجاح!');
        $this->newLine();
        
        $this->line('📋 معلومات تسجيل الدخول:');
        $this->line('====================');
        $this->newLine();
        
        $this->line('👑 السوبر أدمن:');
        $this->line('   البريد الإلكتروني: admin@mrshife.com');
        $this->line('   كلمة المرور: password123');
        $this->line('   الصلاحيات: جميع الصلاحيات');
        $this->newLine();
        
        $this->line('👥 المديرين التجريبيين:');
        $this->line('   مدير عام: manager@mrshife.com / password123');
        $this->line('   مدير محتوى: content@mrshife.com / password123');
        $this->line('   مدير عملاء: customers@mrshife.com / password123');
        $this->line('   مدير مالي: finance@mrshife.com / password123');
        $this->line('   مشرف: supervisor@mrshife.com / password123');
        $this->newLine();
        
        $this->line('🌐 رابط لوحة التحكم:');
        $this->line('   ' . url('/admin'));
        $this->newLine();
        
        $this->line('🎯 الأدوار المتاحة:');
        $this->line('   - super_admin (جميع الصلاحيات)');
        $this->line('   - مدير عام (صلاحيات إدارية شاملة)');
        $this->line('   - مدير محتوى (إدارة المنتجات والفئات)');
        $this->line('   - مدير عملاء (إدارة العملاء والطلبات)');
        $this->line('   - مدير مالي (إدارة الاشتراكات والمدفوعات)');
        $this->line('   - مشرف (صلاحيات عرض فقط)');
        $this->newLine();
        
        $this->info('💡 نصائح:');
        $this->line('   - استخدم السوبر أدمن لإعداد النظام');
        $this->line('   - يمكنك تعديل الأدوار والصلاحيات من لوحة التحكم');
        $this->line('   - تأكد من تغيير كلمات المرور في بيئة الإنتاج');
    }
}
