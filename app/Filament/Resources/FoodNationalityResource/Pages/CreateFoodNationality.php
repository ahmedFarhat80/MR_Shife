<?php

namespace App\Filament\Resources\FoodNationalityResource\Pages;

use App\Filament\Resources\FoodNationalityResource;
use Filament\Resources\Pages\CreateRecord;
use App\Helpers\CacheHelper;

class CreateFoodNationality extends CreateRecord
{
    protected static string $resource = FoodNationalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الجنسية بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // مسح الكاش قبل الإنشاء
        CacheHelper::clearNationalities();

        return $data;
    }
}
