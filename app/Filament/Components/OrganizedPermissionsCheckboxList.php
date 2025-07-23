<?php

namespace App\Filament\Components;

use Filament\Forms\Components\CheckboxList;
use App\Services\AdminTranslationService;
use Spatie\Permission\Models\Permission;

class OrganizedPermissionsCheckboxList extends CheckboxList
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('الصلاحيات')
            ->searchable()
            ->bulkToggleable()
            ->gridDirection('row')
            ->columns(1)
            ->options($this->getOrganizedOptions())
            ->descriptions($this->getPermissionDescriptions())
            ->helperText('اختر الصلاحيات التي تريد منحها لهذا الدور')
            ->columnSpanFull();
    }

    protected function getOrganizedOptions(): array
    {
        $organizedPermissions = AdminTranslationService::getOrganizedPermissions();
        $allPermissions = Permission::where('guard_name', 'admin')->get();
        $options = [];

        foreach ($organizedPermissions as $groupName => $permissions) {
            // إضافة عنوان المجموعة مع تنسيق خاص
            $options["group_" . md5($groupName)] = [
                'label' => $groupName,
                'disabled' => true,
                'class' => 'font-bold text-lg text-primary-600 bg-primary-50 p-2 rounded-lg border-l-4 border-primary-500'
            ];
            
            // إضافة الصلاحيات تحت المجموعة
            foreach ($permissions as $permissionKey => $permissionName) {
                $permission = $allPermissions->where('name', $permissionKey)->first();
                
                if ($permission) {
                    $options[$permission->id] = [
                        'label' => "✓ {$permissionName}",
                        'class' => 'ml-6 text-gray-700 hover:text-primary-600'
                    ];
                }
            }
        }

        return $options;
    }

    protected function getPermissionDescriptions(): array
    {
        return Permission::where('guard_name', 'admin')
            ->pluck('name', 'id')
            ->mapWithKeys(function ($name, $id) {
                return [$id => AdminTranslationService::getPermissionDescription($name)];
            })
            ->toArray();
    }

    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }
}
