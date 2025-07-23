<?php

namespace App\Filament\UserMenuItems;

use Filament\Navigation\UserMenuItem;
use Illuminate\Support\Facades\Session;

class LanguageSwitchMenuItem
{
    public static function make(): array
    {
        $currentLocale = Session::get('locale', 'ar');
        
        return [
            UserMenuItem::make()
                ->label($currentLocale === 'ar' ? 'English' : 'العربية')
                ->icon('heroicon-o-language')
                ->url('#')
                ->openUrlInNewTab(false)
                ->extraAttributes([
                    'onclick' => 'toggleLanguage()',
                    'class' => 'language-switch-item'
                ])
        ];
    }
}
