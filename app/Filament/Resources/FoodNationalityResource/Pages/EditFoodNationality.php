<?php

namespace App\Filament\Resources\FoodNationalityResource\Pages;

use App\Filament\Resources\FoodNationalityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Helpers\CacheHelper;

class EditFoodNationality extends EditRecord
{
    protected static string $resource = FoodNationalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->after(function () {
                    // مسح الكاش بعد الحذف
                    CacheHelper::clearNationalities();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الجنسية بنجاح';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // مسح الكاش قبل الحفظ
        CacheHelper::clearNationalities();

        return $data;
    }
}
