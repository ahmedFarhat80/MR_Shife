<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف المدير')
                ->requiresConfirmation()
                ->modalHeading('حذف المدير')
                ->modalDescription('هل أنت متأكد من حذف هذا المدير؟ لا يمكن التراجع عن هذا الإجراء.')
                ->modalSubmitActionLabel('حذف')
                ->modalCancelActionLabel('إلغاء')
                ->visible(fn ($record) => $record && $record->email !== 'admin@mrshife.com'), // حماية السوبر أدمن
        ];
    }

    protected function authorizeAccess(): void
    {
        // منع تعديل السوبر أدمن
        if ($this->record->email === 'admin@mrshife.com') {
            abort(403, 'لا يمكن تعديل السوبر أدمن');
        }

        parent::authorizeAccess();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // إزالة كلمة المرور من البيانات المعروضة لأنها مشفرة
        unset($data['password']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate that roles are selected
        if (empty($data['roles']) || count($data['roles']) === 0) {
            Notification::make()
                ->title('خطأ في البيانات')
                ->body('يجب اختيار دور واحد على الأقل للمدير')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $admin = $this->record;

        // Sync roles for the admin
        if (!empty($this->data['roles'])) {
            $admin->syncRoles($this->data['roles']);
        }

        // تحديث الكاش فوراً بعد تعديل المدير
        AdminResource::clearAllCaches();

        Notification::make()
            ->title('تم تحديث المدير بنجاح')
            ->body("تم تحديث بيانات المدير {$admin->name} والأدوار بنجاح")
            ->success()
            ->send();
    }
}
