<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\{Merchant, Customer, Product};
use Illuminate\Support\Facades\{Cache, DB};
use Carbon\Carbon;

class PerformanceChartsWidget extends ChartWidget
{
    protected static ?string $heading = null;

    public function getHeading(): string
    {
        return __('chart.monthly_performance');
    }
    protected static ?int $sort = 5;
    protected static bool $isLazy = true;
    protected int $cacheTime = 600; // 10 minutes cache

    protected function getData(): array
    {
        return Cache::remember('performance_charts_data', $this->cacheTime, function () {
            return $this->getPerformanceData();
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'الشهر',
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'العدد',
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    private function getPerformanceData(): array
    {
        $months = [];
        $customerData = [];
        $merchantData = [];
        $productData = [];
        $subscriptionData = [];

        // Get last 6 months data
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Month label
            $months[] = $date->locale('ar')->format('M Y');

            // Customer registrations
            $customerCount = Customer::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $customerData[] = $customerCount;

            // Merchant registrations
            $merchantCount = Merchant::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $merchantData[] = $merchantCount;

            // Product additions
            $productCount = Product::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $productData[] = $productCount;

            // Active subscriptions (snapshot at end of month)
            $subscriptionCount = Merchant::where('subscription_status', 'active')
                ->where('updated_at', '<=', $endOfMonth)
                ->count();
            $subscriptionData[] = $subscriptionCount;
        }

        return [
            'datasets' => [
                [
                    'label' => __('chart.customer_registrations'),
                    'data' => $customerData,
                    'borderColor' => '#3B82F6', // Blue
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => __('chart.merchant_registrations'),
                    'data' => $merchantData,
                    'borderColor' => '#10B981', // Green
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => __('chart.products_added'),
                    'data' => $productData,
                    'borderColor' => '#F59E0B', // Amber
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#F59E0B',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => __('chart.active_subscriptions'),
                    'data' => $subscriptionData,
                    'borderColor' => '#8B5CF6', // Purple
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                ],
            ],
            'labels' => $months,
        ];
    }

    public function getDescription(): ?string
    {
        return __('chart.monthly_performance_desc');
    }
}
