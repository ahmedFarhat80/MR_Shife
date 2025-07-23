<?php

namespace App\Filament\Resources\InternalCategoryResource\Pages;

use App\Filament\Resources\InternalCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInternalCategory extends ViewRecord
{
    protected static string $resource = InternalCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
}
