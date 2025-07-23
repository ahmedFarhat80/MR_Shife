<?php

namespace App\Filament\Resources\InternalCategoryResource\Pages;

use App\Filament\Resources\InternalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Helpers\CacheHelper;

class EditInternalCategory extends EditRecord
{
    protected static string $resource = InternalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->after(function () {
                    // مسح الكاش بعد الحذف
                    CacheHelper::clearCategories();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الفئة بنجاح';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // مسح الكاش قبل الحفظ
        CacheHelper::clearCategories();

        return $data;
    }
}
