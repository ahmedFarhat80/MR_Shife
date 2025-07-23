<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Configure Language Switch for Filament
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['ar', 'en']) // Arabic and English
                ->labels([
                    'ar' => 'العربية',
                    'en' => 'English',
                ])
                ->flags([
                    'ar' => 'https://flagcdn.com/w40/sa.png',
                    'en' => 'https://flagcdn.com/w40/us.png',
                ])
                ->circular()
                ->visible(insidePanels: true, outsidePanels: true)
                ->renderHook('panels::user-menu.start'); // Place it inside user menu
        });
    }
}
