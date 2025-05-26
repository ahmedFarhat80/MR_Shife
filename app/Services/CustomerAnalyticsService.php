<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerAnalyticsService
{
    /**
     * Get customer statistics dashboard.
     */
    public function getCustomerDashboard(Customer $customer): array
    {
        return [
            'overview' => $this->getCustomerOverview($customer),
            'spending_analytics' => $this->getSpendingAnalytics($customer),
            'order_patterns' => $this->getOrderPatterns($customer),
            'merchant_preferences' => $this->getMerchantPreferences($customer),
            'loyalty_status' => $this->getLoyaltyStatus($customer),
        ];
    }

    /**
     * Get customer overview statistics.
     */
    public function getCustomerOverview(Customer $customer): array
    {
        return [
            'total_orders' => $customer->total_orders,
            'completed_orders' => $customer->completed_orders_count,
            'total_spent' => $customer->total_spent,
            'average_order_value' => $customer->average_order_value,
            'customer_tier' => $customer->customer_tier,
            'loyalty_points' => $customer->loyalty_points,
            'is_frequent_buyer' => $customer->isFrequentBuyer(),
            'account_age_days' => $customer->created_at->diffInDays(now()),
            'last_order_days_ago' => $customer->orders()->latest()->first()?->created_at?->diffInDays(now()),
        ];
    }

    /**
     * Get spending analytics for the customer.
     */
    public function getSpendingAnalytics(Customer $customer): array
    {
        $orders = $customer->completedOrders();

        return [
            'monthly_spending' => $this->getMonthlySpending($customer),
            'spending_by_category' => $this->getSpendingByCategory($customer),
            'peak_ordering_hours' => $this->getPeakOrderingHours($customer),
            'spending_trend' => $this->getSpendingTrend($customer),
        ];
    }

    /**
     * Get monthly spending for the last 12 months.
     */
    public function getMonthlySpending(Customer $customer): Collection
    {
        return $customer->completedOrders()
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(total_amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => sprintf('%04d-%02d', $item->year, $item->month),
                    'total' => (float) $item->total,
                ];
            });
    }

    /**
     * Get spending by product category.
     */
    public function getSpendingByCategory(Customer $customer): Collection
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.customer_id', $customer->id)
            ->where('orders.status', 'delivered')
            ->selectRaw('categories.id, categories.name, SUM(order_items.total_price) as total_spent')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_spent')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->id,
                    'category_name' => json_decode($item->name, true),
                    'total_spent' => (float) $item->total_spent,
                ];
            });
    }

    /**
     * Get peak ordering hours.
     */
    public function getPeakOrderingHours(Customer $customer): Collection
    {
        return $customer->orders()
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as order_count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => $item->hour,
                    'order_count' => $item->order_count,
                    'time_label' => sprintf('%02d:00', $item->hour),
                ];
            });
    }

    /**
     * Get spending trend (last 6 months vs previous 6 months).
     */
    public function getSpendingTrend(Customer $customer): array
    {
        $lastSixMonths = $customer->completedOrders()
            ->where('created_at', '>=', now()->subMonths(6))
            ->sum('total_amount');

        $previousSixMonths = $customer->completedOrders()
            ->whereBetween('created_at', [now()->subMonths(12), now()->subMonths(6)])
            ->sum('total_amount');

        $trend = 0;
        if ($previousSixMonths > 0) {
            $trend = (($lastSixMonths - $previousSixMonths) / $previousSixMonths) * 100;
        }

        return [
            'last_six_months' => (float) $lastSixMonths,
            'previous_six_months' => (float) $previousSixMonths,
            'trend_percentage' => round($trend, 2),
            'trend_direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get order patterns analysis.
     */
    public function getOrderPatterns(Customer $customer): array
    {
        return [
            'orders_by_day_of_week' => $this->getOrdersByDayOfWeek($customer),
            'average_days_between_orders' => $this->getAverageDaysBetweenOrders($customer),
            'most_ordered_products' => $this->getMostOrderedProducts($customer),
            'order_size_distribution' => $this->getOrderSizeDistribution($customer),
        ];
    }

    /**
     * Get orders by day of week.
     */
    public function getOrdersByDayOfWeek(Customer $customer): Collection
    {
        return $customer->orders()
            ->selectRaw('DAYOFWEEK(created_at) as day_of_week, COUNT(*) as order_count')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->map(function ($item) {
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return [
                    'day_number' => $item->day_of_week,
                    'day_name' => $days[$item->day_of_week - 1],
                    'order_count' => $item->order_count,
                ];
            });
    }

    /**
     * Get average days between orders.
     */
    public function getAverageDaysBetweenOrders(Customer $customer): ?float
    {
        $orders = $customer->orders()
            ->orderBy('created_at')
            ->pluck('created_at')
            ->toArray();

        if (count($orders) < 2) {
            return null;
        }

        $totalDays = 0;
        for ($i = 1; $i < count($orders); $i++) {
            $totalDays += $orders[$i]->diffInDays($orders[$i - 1]);
        }

        return round($totalDays / (count($orders) - 1), 1);
    }

    /**
     * Get most ordered products.
     */
    public function getMostOrderedProducts(Customer $customer): Collection
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.customer_id', $customer->id)
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as total_quantity, COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->id,
                    'product_name' => json_decode($item->name, true),
                    'total_quantity' => $item->total_quantity,
                    'order_count' => $item->order_count,
                ];
            });
    }

    /**
     * Get order size distribution.
     */
    public function getOrderSizeDistribution(Customer $customer): array
    {
        $orders = $customer->completedOrders()->pluck('total_amount');

        if ($orders->isEmpty()) {
            return [];
        }

        return [
            'small_orders' => $orders->filter(fn($amount) => $amount < 50)->count(),
            'medium_orders' => $orders->filter(fn($amount) => $amount >= 50 && $amount < 150)->count(),
            'large_orders' => $orders->filter(fn($amount) => $amount >= 150)->count(),
            'average_order_size' => round($orders->avg(), 2),
            'largest_order' => $orders->max(),
            'smallest_order' => $orders->min(),
        ];
    }

    /**
     * Get merchant preferences.
     */
    public function getMerchantPreferences(Customer $customer): Collection
    {
        return $customer->orders()
            ->with('merchant')
            ->selectRaw('merchant_id, COUNT(*) as order_count, SUM(total_amount) as total_spent')
            ->groupBy('merchant_id')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'merchant_id' => $order->merchant_id,
                    'merchant_name' => $order->merchant?->getTranslations('name'),
                    'order_count' => $order->order_count,
                    'total_spent' => (float) $order->total_spent,
                    'average_order_value' => round($order->total_spent / $order->order_count, 2),
                ];
            });
    }

    /**
     * Get loyalty status and recommendations.
     */
    public function getLoyaltyStatus(Customer $customer): array
    {
        $earnedPoints = $customer->calculateEarnedLoyaltyPoints();
        $currentPoints = $customer->loyalty_points;
        $pointsToNextTier = $this->getPointsToNextTier($customer);

        return [
            'current_tier' => $customer->customer_tier,
            'current_points' => $currentPoints,
            'earned_points' => $earnedPoints,
            'points_difference' => $earnedPoints - $currentPoints,
            'points_to_next_tier' => $pointsToNextTier,
            'next_tier' => $this->getNextTier($customer->customer_tier),
            'tier_benefits' => $this->getTierBenefits($customer->customer_tier),
        ];
    }

    /**
     * Get points needed to reach next tier.
     */
    private function getPointsToNextTier(Customer $customer): ?int
    {
        $currentTier = $customer->customer_tier;
        $currentPoints = $customer->loyalty_points;

        $tierThresholds = [
            'bronze' => 50,   // 500 SAR spent
            'silver' => 200,  // 2000 SAR spent
            'gold' => 500,    // 5000 SAR spent
            'platinum' => null, // No next tier
        ];

        $nextTierPoints = $tierThresholds[$currentTier] ?? null;

        return $nextTierPoints ? max(0, $nextTierPoints - $currentPoints) : null;
    }

    /**
     * Get next tier name.
     */
    private function getNextTier(string $currentTier): ?string
    {
        $tiers = ['bronze' => 'silver', 'silver' => 'gold', 'gold' => 'platinum'];
        return $tiers[$currentTier] ?? null;
    }

    /**
     * Get tier benefits.
     */
    private function getTierBenefits(string $tier): array
    {
        $benefits = [
            'bronze' => ['Basic customer support', 'Order tracking'],
            'silver' => ['Priority support', 'Free delivery on orders over 100 SAR', '5% loyalty points bonus'],
            'gold' => ['Premium support', 'Free delivery on orders over 50 SAR', '10% loyalty points bonus', 'Early access to promotions'],
            'platinum' => ['VIP support', 'Free delivery on all orders', '15% loyalty points bonus', 'Exclusive offers', 'Personal account manager'],
        ];

        return $benefits[$tier] ?? [];
    }
} 