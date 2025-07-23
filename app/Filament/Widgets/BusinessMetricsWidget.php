<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Merchant, Customer, Product};
use Illuminate\Support\Facades\{Cache, DB};
use Carbon\Carbon;

class BusinessMetricsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected static bool $isLazy = true;
    protected int $cacheTime = 300; // 5 minutes cache

    protected function getStats(): array
    {
        return Cache::remember('business_metrics_stats', $this->cacheTime, function () {
            $metrics = $this->getBusinessMetrics();

            return [
                Stat::make(__('business.verification_rate'), $metrics['verification_rate'] . '%')
                    ->description($metrics['verification_description'])
                    ->descriptionIcon('heroicon-m-shield-check')
                    ->color($this->getVerificationColor($metrics['verification_rate']))
                    ->chart($this->getVerificationChart()),

                Stat::make(__('business.active_merchants_stat'), number_format($metrics['active_merchants']))
                    ->description($metrics['active_merchants_description'])
                    ->descriptionIcon('heroicon-m-building-storefront')
                    ->color('success')
                    ->chart($this->getActiveMerchantsChart()),

                Stat::make(__('business.avg_products'), $metrics['avg_products_per_merchant'])
                    ->description($metrics['products_description'])
                    ->descriptionIcon('heroicon-m-cube')
                    ->color('info')
                    ->chart($this->getProductsChart()),

                Stat::make(__('widget.subscription_rate'), $metrics['subscription_rate'] . '%')
                    ->description($metrics['subscription_description'])
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color($this->getSubscriptionColor($metrics['subscription_rate']))
                    ->chart($this->getSubscriptionTrendChart()),
            ];
        });
    }

    private function getBusinessMetrics(): array
    {
        // Optimized query to get all metrics at once
        $results = DB::select("
            SELECT
                -- Verification metrics
                (SELECT COUNT(*) FROM customers WHERE phone_verified_at IS NOT NULL) as verified_customers,
                (SELECT COUNT(*) FROM customers) as total_customers,
                (SELECT COUNT(*) FROM merchants WHERE phone_verified_at IS NOT NULL) as verified_merchants,
                (SELECT COUNT(*) FROM merchants) as total_merchants,

                -- Active merchants
                (SELECT COUNT(*) FROM merchants WHERE status = 'active') as active_merchants,

                -- Products metrics
                (SELECT COUNT(*) FROM products) as total_products,
                (SELECT COUNT(DISTINCT merchant_id) FROM products) as merchants_with_products,

                -- Subscription metrics
                (SELECT COUNT(*) FROM merchants WHERE subscription_status = 'active') as active_subscriptions,
                (SELECT COUNT(*) FROM merchants WHERE subscription_status IS NOT NULL) as merchants_with_subscription
        ");

        $data = $results[0];

        // Calculate rates
        $customerVerificationRate = $data->total_customers > 0
            ? round(($data->verified_customers / $data->total_customers) * 100, 1)
            : 0;

        $merchantVerificationRate = $data->total_merchants > 0
            ? round(($data->verified_merchants / $data->total_merchants) * 100, 1)
            : 0;

        $overallVerificationRate = round(($customerVerificationRate + $merchantVerificationRate) / 2, 1);

        $avgProductsPerMerchant = $data->merchants_with_products > 0
            ? round($data->total_products / $data->merchants_with_products, 1)
            : 0;

        $subscriptionRate = $data->merchants_with_subscription > 0
            ? round(($data->active_subscriptions / $data->merchants_with_subscription) * 100, 1)
            : 0;

        return [
            'verification_rate' => $overallVerificationRate,
            'verification_description' => __('business.customers_merchants_verification', [
                'customers' => $customerVerificationRate,
                'merchants' => $merchantVerificationRate
            ]),
            'active_merchants' => $data->active_merchants,
            'active_merchants_description' => __('stats.from_total', ['total' => number_format($data->total_merchants)]),
            'avg_products_per_merchant' => $avgProductsPerMerchant,
            'products_description' => __('business.total_products', ['count' => number_format($data->total_products)]),
            'subscription_rate' => $subscriptionRate,
            'subscription_description' => __('business.subscription_active', ['count' => number_format($data->active_subscriptions)]),
        ];
    }

    private function getVerificationChart(): array
    {
        return Cache::remember('verification_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $verifiedToday = DB::table('customers')
                    ->whereDate('phone_verified_at', $date)
                    ->count() +
                    DB::table('merchants')
                    ->whereDate('phone_verified_at', $date)
                    ->count();

                $data[] = $verifiedToday;
            }
            return $data;
        });
    }

    private function getActiveMerchantsChart(): array
    {
        return Cache::remember('active_merchants_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $activeCount = Merchant::where('status', 'active')
                    ->whereDate('updated_at', '<=', $date)
                    ->count();

                $data[] = $activeCount;
            }
            return $data;
        });
    }

    private function getProductsChart(): array
    {
        return Cache::remember('products_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $productsCount = Product::whereDate('created_at', $date)->count();
                $data[] = $productsCount;
            }
            return $data;
        });
    }

    private function getSubscriptionTrendChart(): array
    {
        return Cache::remember('subscription_trend_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $subscriptionsCount = Merchant::where('subscription_status', 'active')
                    ->whereDate('updated_at', $date)
                    ->count();

                $data[] = $subscriptionsCount;
            }
            return $data;
        });
    }

    private function getVerificationColor(float $rate): string
    {
        if ($rate >= 80) return 'success';
        if ($rate >= 60) return 'warning';
        return 'danger';
    }

    private function getSubscriptionColor(float $rate): string
    {
        if ($rate >= 70) return 'success';
        if ($rate >= 50) return 'warning';
        return 'danger';
    }
}
