<?php

namespace App\Filament\Resources\FoodNationalityResource\Pages;

use App\Filament\Resources\FoodNationalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Helpers\CacheHelper;

class ListFoodNationalities extends ListRecords
{
    protected static string $resource = FoodNationalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة جنسية جديدة'),
            Actions\Action::make('clear_cache')
                ->label('مسح الكاش')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    CacheHelper::clearNationalities();

                    $this->notify('success', 'تم مسح الكاش بنجاح');
                }),
        ];
    }
}
