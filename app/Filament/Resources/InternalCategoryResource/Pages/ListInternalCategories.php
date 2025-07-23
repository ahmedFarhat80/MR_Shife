<?php

namespace App\Filament\Resources\InternalCategoryResource\Pages;

use App\Filament\Resources\InternalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Helpers\CacheHelper;

class ListInternalCategories extends ListRecords
{
    protected static string $resource = InternalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة فئة جديدة'),
            Actions\Action::make('clear_cache')
                ->label('مسح الكاش')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    CacheHelper::clearCategories();

                    $this->notify('success', 'تم مسح الكاش بنجاح');
                }),
        ];
    }
}
