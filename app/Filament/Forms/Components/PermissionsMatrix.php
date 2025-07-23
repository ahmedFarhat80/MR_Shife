<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use App\Services\AdminTranslationService;
use Spatie\Permission\Models\Permission;

class PermissionsMatrix extends Field
{
    protected string $view = 'filament.forms.components.permissions-matrix';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        // إجبار إعادة التحميل عند التحديث
        $this->reactive();

        // تحديث فوري عند تغيير القيمة
        $this->live();
    }

    public function getPermissionGroups(): array
    {
        $permissions = Permission::where('guard_name', 'admin')->get();
        $groups = [];

        // تجميع الصلاحيات حسب النوع
        foreach ($permissions as $permission) {
            $parts = explode('_', $permission->name);
            $action = $parts[0]; // view, create, update, delete
            $resource = implode('_', array_slice($parts, 1)); // admin, customer, etc.

            // تنظيف اسم المورد
            $resource = str_replace(['any_', '::', '::'], ['', '_', '_'], $resource);

            if (!isset($groups[$resource])) {
                $groups[$resource] = [
                    'name' => $this->getResourceDisplayName($resource),
                    'permissions' => []
                ];
            }

            $groups[$resource]['permissions'][$action] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'label' => $this->getActionDisplayName($action)
            ];
        }

        return $groups;
    }

    private function getResourceDisplayName(string $resource): string
    {
        $names = [
            'admin' => 'المديرين',
            'customer' => 'العملاء',
            'merchant' => 'التجار',
            'product' => 'المنتجات',
            'order' => 'الطلبات',
            'category' => 'الفئات',
            'internal_category' => 'الفئات الداخلية',
            'food_nationality' => 'جنسيات الطعام',
            'subscription_plan' => 'خطط الاشتراك',
            'role' => 'الأدوار',
            'media' => 'الوسائط',
        ];

        return $names[$resource] ?? $resource;
    }

    private function getActionDisplayName(string $action): string
    {
        $actions = [
            'view' => 'عرض',
            'view_any' => 'عرض الكل',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
            'delete_any' => 'حذف الكل',
            'restore' => 'استعادة',
            'restore_any' => 'استعادة الكل',
            'force_delete' => 'حذف نهائي',
            'force_delete_any' => 'حذف نهائي للكل',
            'approve' => 'موافقة',
            'suspend' => 'تعليق',
            'feature' => 'تمييز',
            'cancel' => 'إلغاء',
            'refund' => 'استرداد',
        ];

        return $actions[$action] ?? $action;
    }

    public function getCurrentRecord()
    {
        return $this->getContainer()->getRecord();
    }

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }
}
