<?php

namespace App\Filament\Resources\FoodNationalityResource\Pages;

use App\Filament\Resources\FoodNationalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFoodNationality extends ViewRecord
{
    protected static string $resource = FoodNationalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
