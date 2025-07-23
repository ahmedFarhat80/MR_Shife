<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
// use Awcodes\Curator\CuratorPlugin; // Temporarily disabled

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('MR Shife Admin')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon.ico'))

            ->font('Cairo')
            ->darkMode()
            ->renderHook('panels::body.end', fn() => view('components.language-switch'))
            ->authGuard('admin')
            ->authPasswordBroker('admins')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\MainStatsWidget::class,
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\BusinessMetricsWidget::class,
                \App\Filament\Widgets\UserEngagementWidget::class,
                \App\Filament\Widgets\RecentActivityWidget::class,
                \App\Filament\Widgets\ChartsRowWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\HandleStorageCors::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->unsavedChangesAlerts()
            ->plugins([
                FilamentShieldPlugin::make(),

                // Media Library Plugin - Temporarily disabled
                // Uncomment when needed in the future
                // CuratorPlugin::make()
                //     ->label('مكتبة الوسائط')
                //     ->pluralLabel('مكتبة الوسائط')
                //     ->navigationIcon('heroicon-o-photo')
                //     ->navigationGroup('إعدادات النظام')
                //     ->navigationSort(3),
            ])
            ->navigationGroups([
                __('navigation.user_management'),
                __('navigation.system_settings'),
            ])
            ->userMenuItems([
                'theme-switch' => \Filament\Navigation\MenuItem::make()
                    ->label(fn() => __('theme.toggle'))
                    ->icon('heroicon-o-sun')
                    ->url('javascript:toggleTheme()')
                    ->sort(1),
                'language-switch' => \Filament\Navigation\MenuItem::make()
                    ->label(fn() => app()->getLocale() === 'ar' ? __('language.english') : __('language.arabic'))
                    ->icon('heroicon-o-language')
                    ->url('javascript:toggleLanguage()')
                    ->sort(2),
            ]);
    }
}
