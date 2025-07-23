<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\{Merchant, Customer};
use Illuminate\Support\Facades\{Cache, DB};
use Carbon\Carbon;

class UserEngagementWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected static bool $isLazy = true;
    protected int $cacheTime = 300; // 5 minutes cache

    protected function getStats(): array
    {
        return Cache::remember('user_engagement_stats', $this->cacheTime, function () {
            $engagement = $this->getUserEngagementMetrics();

            return [
                Stat::make(__('engagement.daily_active_users'), number_format($engagement['daily_active']))
                    ->description($engagement['daily_description'])
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success')
                    ->chart($this->getDailyActiveChart()),

                Stat::make(__('widget.new_registrations'), number_format($engagement['new_registrations']))
                    ->description($engagement['registrations_description'])
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('info')
                    ->chart($this->getRegistrationsChart()),

                Stat::make(__('engagement.retention_rate'), $engagement['retention_rate'] . '%')
                    ->description($engagement['retention_description'])
                    ->descriptionIcon('heroicon-m-heart')
                    ->color($this->getRetentionColor($engagement['retention_rate']))
                    ->chart($this->getRetentionChart()),

                Stat::make(__('engagement.avg_session_time'), $engagement['avg_session_time'])
                    ->description($engagement['session_description'])
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning')
                    ->chart($this->getSessionTimeChart()),
            ];
        });
    }

    private function getUserEngagementMetrics(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekAgo = Carbon::now()->subWeek();
        $monthAgo = Carbon::now()->subMonth();

        // Daily active users (users who were updated today - proxy for activity)
        $dailyActiveCustomers = Customer::whereDate('updated_at', $today)->count();
        $dailyActiveMerchants = Merchant::whereDate('updated_at', $today)->count();
        $dailyActive = $dailyActiveCustomers + $dailyActiveMerchants;

        // Yesterday's active users for comparison
        $yesterdayActiveCustomers = Customer::whereDate('updated_at', $yesterday)->count();
        $yesterdayActiveMerchants = Merchant::whereDate('updated_at', $yesterday)->count();
        $yesterdayActive = $yesterdayActiveCustomers + $yesterdayActiveMerchants;

        $dailyChange = $dailyActive - $yesterdayActive;
        $dailyChangeText = $dailyChange >= 0 ? "+{$dailyChange}" : "{$dailyChange}";

        // New registrations today
        $newCustomers = Customer::whereDate('created_at', $today)->count();
        $newMerchants = Merchant::whereDate('created_at', $today)->count();
        $newRegistrations = $newCustomers + $newMerchants;

        // Retention rate (users who were active this week vs last week)
        $thisWeekActive = Customer::where('updated_at', '>=', $weekAgo)->count() +
                         Merchant::where('updated_at', '>=', $weekAgo)->count();
        $totalUsers = Customer::count() + Merchant::count();
        $retentionRate = $totalUsers > 0 ? round(($thisWeekActive / $totalUsers) * 100, 1) : 0;

        // Mock session time (in real app, you'd track this)
        $avgSessionMinutes = rand(5, 25);
        $avgSessionTime = __('engagement.session_time_minutes', ['minutes' => $avgSessionMinutes]);

        return [
            'daily_active' => $dailyActive,
            'daily_description' => __('engagement.change_from_yesterday', ['change' => $dailyChangeText]),
            'new_registrations' => $newRegistrations,
            'registrations_description' => __('engagement.customers_merchants_split', [
                'customers' => $newCustomers,
                'merchants' => $newMerchants
            ]),
            'retention_rate' => $retentionRate,
            'retention_description' => __('engagement.active_this_week', ['total' => number_format($totalUsers)]),
            'avg_session_time' => $avgSessionTime,
            'session_description' => __('engagement.daily_sessions_avg'),
        ];
    }

    private function getDailyActiveChart(): array
    {
        return Cache::remember('daily_active_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $activeCount = Customer::whereDate('updated_at', $date)->count() +
                              Merchant::whereDate('updated_at', $date)->count();

                $data[] = $activeCount;
            }
            return $data;
        });
    }

    private function getRegistrationsChart(): array
    {
        return Cache::remember('registrations_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);

                $registrations = Customer::whereDate('created_at', $date)->count() +
                               Merchant::whereDate('created_at', $date)->count();

                $data[] = $registrations;
            }
            return $data;
        });
    }

    private function getRetentionChart(): array
    {
        return Cache::remember('retention_chart', $this->cacheTime, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $weekBefore = $date->copy()->subWeek();

                $activeThisWeek = Customer::whereBetween('updated_at', [$weekBefore, $date])->count() +
                                 Merchant::whereBetween('updated_at', [$weekBefore, $date])->count();

                $totalAtDate = Customer::where('created_at', '<=', $date)->count() +
                              Merchant::where('created_at', '<=', $date)->count();

                $retentionRate = $totalAtDate > 0 ? round(($activeThisWeek / $totalAtDate) * 100, 1) : 0;
                $data[] = $retentionRate;
            }
            return $data;
        });
    }

    private function getSessionTimeChart(): array
    {
        return Cache::remember('session_time_chart', $this->cacheTime, function () {
            // Mock data - in real app, you'd track actual session times
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $data[] = rand(10, 30); // Random session time in minutes
            }
            return $data;
        });
    }

    private function getRetentionColor(float $rate): string
    {
        if ($rate >= 70) return 'success';
        if ($rate >= 50) return 'warning';
        return 'danger';
    }
}
