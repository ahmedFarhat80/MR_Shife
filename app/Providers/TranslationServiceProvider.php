<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Set locale from session or default to Arabic
        $locale = Session::get('locale', config('app.locale', 'ar'));
        
        // Validate locale
        if (!in_array($locale, ['ar', 'en'])) {
            $locale = 'ar';
        }
        
        App::setLocale($locale);
        
        // Ensure JSON translation files are loaded
        $this->loadJsonTranslationsFrom(resource_path('lang'));
        
        // Set RTL direction for Arabic
        if ($locale === 'ar') {
            config(['filament.direction' => 'rtl']);
        } else {
            config(['filament.direction' => 'ltr']);
        }
    }
}
