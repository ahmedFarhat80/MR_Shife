<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use Illuminate\Support\Facades\{DB, Cache};

class CustomerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    protected int $cacheTime = 900; // 15 minutes cache

    protected function getStats(): array
    {
        return Cache::remember('customer_stats_widget', $this->cacheTime, function () {
            // Get customer statistics (إزالة البيانات المكررة)
            $activeCustomers = Customer::where('status', 'active')->count();
            $verifiedCustomers = Customer::where('phone_verified', true)->count();

        // Get customer tier distribution using subqueries to avoid GROUP BY issues
        $platinumCustomers = DB::select("
            SELECT COUNT(*) as count FROM (
                SELECT customers.id
                FROM customers
                INNER JOIN orders ON customers.id = orders.customer_id
                WHERE orders.status = 'delivered'
                AND customers.deleted_at IS NULL
                GROUP BY customers.id
                HAVING SUM(orders.total_amount) >= 5000
            ) as platinum_customers
        ")[0]->count ?? 0;

        $goldCustomers = DB::select("
            SELECT COUNT(*) as count FROM (
                SELECT customers.id
                FROM customers
                INNER JOIN orders ON customers.id = orders.customer_id
                WHERE orders.status = 'delivered'
                AND customers.deleted_at IS NULL
                GROUP BY customers.id
                HAVING SUM(orders.total_amount) >= 2000 AND SUM(orders.total_amount) < 5000
            ) as gold_customers
        ")[0]->count ?? 0;

        return [
            // إزالة إجمالي العملاء لأنه موجود في النظرة العامة
            Stat::make(__('stats.active_customers'), $activeCustomers)
                ->description(__('stats.active_customers_desc'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('stats.verified_customers'), $verifiedCustomers)
                ->description(__('stats.verified_customers_desc'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            Stat::make(__('stats.platinum_customers'), $platinumCustomers)
                ->description(__('stats.platinum_customers_desc'))
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),

            Stat::make(__('stats.gold_customers'), $goldCustomers)
                ->description(__('stats.gold_customers_desc'))
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
        });
    }
}
