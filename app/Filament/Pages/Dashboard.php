<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\{
    MainStatsWidget,
    BusinessMetricsWidget,
    SystemHealthWidget,
    UserEngagementWidget,
    RecentActivityWidget,
    ChartsRowWidget
};

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // إلغاء الـ custom view للعودة لتصميم Filament الاحترافي
    // protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        // الآن يمكنك التحكم في الـ widgets مع الـ custom view
        return [
            MainStatsWidget::class,
            SystemHealthWidget::class,
            BusinessMetricsWidget::class,
            UserEngagementWidget::class,
            RecentActivityWidget::class,
            ChartsRowWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }

    public function getTitle(): string
    {
        return __('filament.dashboard');
    }

    public function getHeading(): string
    {
        return __('filament.dashboard');
    }

    public function getSubheading(): ?string
    {
        return __('dashboard.overview_description');
    }
}
