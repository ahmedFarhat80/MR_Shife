<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Merchant;


class MerchantStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        // Get merchant statistics (إزالة البيانات المكررة)
        $activeMerchants = Merchant::where('status', 'active')->count();
        $verifiedMerchants = Merchant::where('is_verified', true)->count();
        $approvedMerchants = Merchant::where('is_approved', true)->count();

        // Get subscription statistics
        $expiredSubscriptions = Merchant::where('subscription_status', 'expired')->count();

        // Get business type distribution
        $restaurants = Merchant::where('business_type', 'restaurant')->count();
        $cafes = Merchant::where('business_type', 'cafe')->count();

        return [
            Stat::make(__('stats.active_merchants'), $activeMerchants)
                ->description(__('stats.active_merchants_desc'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('stats.verified_merchants'), $verifiedMerchants)
                ->description(__('stats.verified_merchants_desc'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make(__('stats.approved_merchants'), $approvedMerchants)
                ->description(__('stats.approved_merchants_desc'))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make(__('stats.expired_subscriptions'), $expiredSubscriptions)
                ->description(__('stats.expired_subscriptions_desc'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make(__('stats.restaurants'), $restaurants)
                ->description(__('stats.restaurants_desc'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make(__('stats.cafes'), $cafes)
                ->description(__('stats.cafes_desc'))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('warning'),
        ];
    }
}
