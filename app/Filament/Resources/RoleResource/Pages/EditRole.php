<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف الدور')
                ->requiresConfirmation()
                ->modalHeading('حذف الدور')
                ->modalDescription('هل أنت متأكد من حذف هذا الدور؟ لا يمكن التراجع عن هذا الإجراء.')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->visible(fn ($record) => $record && $record->name !== 'super_admin'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load current permissions for the role
        $role = $this->record;

        // إجبار إعادة تحميل الصلاحيات من قاعدة البيانات
        $role->unsetRelation('permissions');
        $role->load('permissions');

        $data['permissions'] = $role->permissions->pluck('id')->toArray();

        // تسجيل للتأكد من تحميل الصلاحيات الصحيحة
        Log::info('Loading permissions for role', [
            'role_id' => $role->id,
            'role_name' => $role->name,
            'permissions' => $data['permissions']
        ]);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // تأكد من أن الصلاحيات array من integers
        if (isset($data['permissions'])) {
            $data['permissions'] = array_map('intval', array_filter($data['permissions']));

            // تسجيل البيانات المرسلة للتأكد
            Log::info('Permissions data before save', [
                'role_id' => $this->record->id,
                'permissions_data' => $data['permissions']
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $role = $this->record;

        // Sync permissions for the role
        if (isset($this->data['permissions']) && is_array($this->data['permissions'])) {
            // تحويل القيم إلى integers وإزالة القيم الفارغة
            $permissionIds = array_map('intval', array_filter($this->data['permissions']));

            // مسح كاش الصلاحيات قبل التحديث
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // تحديث الصلاحيات
            $role->syncPermissions($permissionIds);

            // مسح كاش الصلاحيات مرة أخرى بعد التحديث
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // إجبار إعادة تحميل الصلاحيات من قاعدة البيانات
            $role->unsetRelation('permissions');
            $role->load('permissions');

            // التحقق من أن الصلاحيات تم حفظها بشكل صحيح
            $savedPermissions = $role->permissions->pluck('id')->toArray();
            sort($permissionIds);
            sort($savedPermissions);

            $isSuccessful = $permissionIds === $savedPermissions;

            // تسجيل للتأكد من الحفظ
            Log::info('Role permissions synced', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions_sent' => $permissionIds,
                'permissions_saved' => $savedPermissions,
                'sync_successful' => $isSuccessful,
                'cache_cleared' => true
            ]);

            // إذا لم يتم الحفظ بشكل صحيح، حاول مرة أخرى
            if (!$isSuccessful) {
                Log::warning('Permissions sync failed, retrying...');
                $role->syncPermissions($permissionIds);
                $role->unsetRelation('permissions');
                $role->load('permissions');
            }
        }

        // إجبار إعادة تحميل النموذج
        $this->fillForm();

        Notification::make()
            ->title('تم تحديث الدور بنجاح')
            ->body("تم تحديث الدور {$role->name} والصلاحيات بنجاح")
            ->success()
            ->send();
    }
}
