<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Session;

class LanguageSwitch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static string $view = 'filament.pages.language-switch';
    protected static bool $shouldRegisterNavigation = false;

    public function switchToArabic(): void
    {
        Session::put('locale', 'ar');
        app()->setLocale('ar');
        $this->redirect(request()->header('Referer'));
    }

    public function switchToEnglish(): void
    {
        Session::put('locale', 'en');
        app()->setLocale('en');
        $this->redirect(request()->header('Referer'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('arabic')
                ->label('العربية')
                ->icon('heroicon-o-language')
                ->action('switchToArabic'),
            
            Action::make('english')
                ->label('English')
                ->icon('heroicon-o-language')
                ->action('switchToEnglish'),
        ];
    }
}
