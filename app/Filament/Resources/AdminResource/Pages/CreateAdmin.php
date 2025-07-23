<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        $admin = $this->record;

        // Assign roles to the admin
        if (!empty($this->data['roles'])) {
            $admin->syncRoles($this->data['roles']);
        }

        // تحديث الكاش فوراً بعد إنشاء المدير
        AdminResource::clearAllCaches();

        Notification::make()
            ->title('تم إنشاء المدير بنجاح')
            ->body("تم إنشاء المدير {$admin->name} وتعيين الأدوار بنجاح")
            ->success()
            ->send();
    }
}
