<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Customer, Merchant, Product};
use Illuminate\Support\Facades\Cache;

class MainStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        return Cache::remember('main_dashboard_stats', 300, function () {
            // الإحصائيات الأساسية فقط - بدون تكرار
            $totalCustomers = Customer::count();
            $totalMerchants = Merchant::count();
            $totalProducts = Product::count();
            $activeSubscriptions = Merchant::where('subscription_status', 'active')->count();

            return [
                Stat::make(__('stats.total_customers'), number_format($totalCustomers))
                    ->description(__('stats.total_customers_desc'))
                    ->descriptionIcon('heroicon-m-users')
                    ->color('primary')
                    ->chart([7, 2, 10, 3, 15, 4, 17]),

                Stat::make(__('stats.total_merchants'), number_format($totalMerchants))
                    ->description(__('stats.total_merchants_desc'))
                    ->descriptionIcon('heroicon-m-building-storefront')
                    ->color('success')
                    ->chart([15, 4, 10, 2, 12, 4, 12]),

                Stat::make(__('product.total'), number_format($totalProducts))
                    ->description(__('product.total_desc'))
                    ->descriptionIcon('heroicon-m-cube')
                    ->color('warning')
                    ->chart([3, 8, 5, 10, 7, 12, 9]),

                Stat::make(__('stats.active_subscriptions'), number_format($activeSubscriptions))
                    ->description(__('stats.active_subscriptions_desc'))
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color('info')
                    ->chart([2, 5, 3, 8, 6, 9, 7]),
            ];
        });
    }
}
