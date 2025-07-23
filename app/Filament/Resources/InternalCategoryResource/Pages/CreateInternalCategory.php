<?php

namespace App\Filament\Resources\InternalCategoryResource\Pages;

use App\Filament\Resources\InternalCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Helpers\CacheHelper;

class CreateInternalCategory extends CreateRecord
{
    protected static string $resource = InternalCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الفئة بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // مسح الكاش قبل الإنشاء
        CacheHelper::clearCategories();

        return $data;
    }
}
