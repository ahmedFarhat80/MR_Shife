<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Merchant, Customer, Product};
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    protected static bool $isLazy = true;
    protected int $cacheTime = 120; // 2 minutes cache

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        return Cache::remember('recent_activity_stats', $this->cacheTime, function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            // Get recent activity counts
            $newCustomersToday = Customer::whereDate('created_at', $today)->count();
            $newMerchantsToday = Merchant::whereDate('created_at', $today)->count();
            $newProductsToday = Product::whereDate('created_at', $today)->count();
            $activeSubscriptionsToday = Merchant::where('subscription_status', 'active')
                ->whereDate('updated_at', $today)->count();

            // Get yesterday's counts for comparison
            $newCustomersYesterday = Customer::whereDate('created_at', $yesterday)->count();
            $newMerchantsYesterday = Merchant::whereDate('created_at', $yesterday)->count();
            $newProductsYesterday = Product::whereDate('created_at', $yesterday)->count();
            $activeSubscriptionsYesterday = Merchant::where('subscription_status', 'active')
                ->whereDate('updated_at', $yesterday)->count();

            return [
                Stat::make(__('stats.new_customers_today'), $newCustomersToday)
                    ->description($this->getChangeDescription($newCustomersToday, $newCustomersYesterday, __('stats.customer_unit')))
                    ->descriptionIcon($newCustomersToday >= $newCustomersYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($newCustomersToday >= $newCustomersYesterday ? 'success' : 'danger'),

                Stat::make(__('stats.new_merchants_today'), $newMerchantsToday)
                    ->description($this->getChangeDescription($newMerchantsToday, $newMerchantsYesterday, __('stats.merchant_unit')))
                    ->descriptionIcon($newMerchantsToday >= $newMerchantsYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($newMerchantsToday >= $newMerchantsYesterday ? 'success' : 'danger'),

                Stat::make(__('stats.new_products_today'), $newProductsToday)
                    ->description($this->getChangeDescription($newProductsToday, $newProductsYesterday, __('stats.product_unit')))
                    ->descriptionIcon($newProductsToday >= $newProductsYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($newProductsToday >= $newProductsYesterday ? 'success' : 'danger'),

                Stat::make(__('stats.active_subscriptions_today'), $activeSubscriptionsToday)
                    ->description($this->getChangeDescription($activeSubscriptionsToday, $activeSubscriptionsYesterday, __('stats.subscription_unit')))
                    ->descriptionIcon($activeSubscriptionsToday >= $activeSubscriptionsYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($activeSubscriptionsToday >= $activeSubscriptionsYesterday ? 'success' : 'danger'),
            ];
        });
    }

    private function getChangeDescription(int $today, int $yesterday, string $unit): string
    {
        $change = $today - $yesterday;

        if ($change > 0) {
            return __('stats.change_from_yesterday', ['count' => $change, 'unit' => $unit]);
        } elseif ($change < 0) {
            return __('stats.less_than_yesterday', ['count' => abs($change), 'unit' => $unit]);
        } else {
            return __('stats.no_change_yesterday');
        }
    }
}
