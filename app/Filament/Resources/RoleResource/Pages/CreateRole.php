<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set guard_name for admin
        $data['guard_name'] = 'admin';

        return $data;
    }

    protected function afterCreate(): void
    {
        $role = $this->record;

        // Assign permissions to the role
        if (!empty($this->data['permissions'])) {
            // مسح كاش الصلاحيات قبل التحديث
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $role->syncPermissions($this->data['permissions']);

            // مسح كاش الصلاحيات مرة أخرى بعد التحديث
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        // إعادة تحميل العلاقات
        $role->unsetRelation('permissions');
        $role->load('permissions');

        Notification::make()
            ->title('تم إنشاء الدور بنجاح')
            ->body("تم إنشاء الدور {$role->name} وتعيين الصلاحيات بنجاح")
            ->success()
            ->send();
    }
}
