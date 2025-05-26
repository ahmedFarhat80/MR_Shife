<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private OrderService $orderService,
        private ProductService $productService
    ) {}

    /**
     * Get dashboard analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $statistics = $this->orderService->getDashboardStatistics($serviceProviderId);

            return $this->apiResponse->success(
                'Dashboard analytics retrieved successfully',
                $statistics
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve dashboard analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get orders analytics.
     */
    public function orders(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:today,week,month,year'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $period = $request->input('period', 'today');
            
            $statistics = $this->orderService->getOrdersStatistics($serviceProviderId, $period);

            return $this->apiResponse->success(
                'Orders analytics retrieved successfully',
                [
                    'period' => $period,
                    'statistics' => $statistics
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve orders analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get revenue analytics.
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:week,month,year'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $period = $request->input('period', 'month');
            
            $statistics = $this->orderService->getRevenueStatistics($serviceProviderId, $period);

            return $this->apiResponse->success(
                'Revenue analytics retrieved successfully',
                $statistics
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve revenue analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get inventory analytics.
     */
    public function inventory(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            
            $lowStockProducts = $this->productService->getLowStockProducts($serviceProviderId, 10);
            $outOfStockProducts = $this->productService->getOutOfStockProducts($serviceProviderId);
            $featuredProducts = $this->productService->getFeaturedProducts($serviceProviderId);
            
            $totalProducts = $this->productService->getProducts($serviceProviderId)->count();
            $availableProducts = $this->productService->getProducts($serviceProviderId, ['is_available' => true])->count();

            return $this->apiResponse->success(
                'Inventory analytics retrieved successfully',
                [
                    'summary' => [
                        'total_products' => $totalProducts,
                        'available_products' => $availableProducts,
                        'unavailable_products' => $totalProducts - $availableProducts,
                        'featured_products' => $featuredProducts->count(),
                        'low_stock_products' => $lowStockProducts->count(),
                        'out_of_stock_products' => $outOfStockProducts->count(),
                    ],
                    'alerts' => [
                        'low_stock_count' => $lowStockProducts->count(),
                        'out_of_stock_count' => $outOfStockProducts->count(),
                    ]
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve inventory analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get popular products analytics.
     */
    public function popularProducts(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:week,month,year',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $period = $request->input('period', 'month');
            $limit = $request->input('limit', 10);

            // This would require more complex queries to get popular products
            // For now, we'll return a placeholder response
            return $this->apiResponse->success(
                'Popular products analytics retrieved successfully',
                [
                    'period' => $period,
                    'limit' => $limit,
                    'message' => 'Popular products analytics feature coming soon'
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve popular products analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get sales trends.
     */
    public function salesTrends(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:week,month,year',
            'granularity' => 'nullable|string|in:day,week,month'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $period = $request->input('period', 'month');
            $granularity = $request->input('granularity', 'day');

            // This would require more complex queries to get sales trends
            // For now, we'll return a placeholder response
            return $this->apiResponse->success(
                'Sales trends analytics retrieved successfully',
                [
                    'period' => $period,
                    'granularity' => $granularity,
                    'message' => 'Sales trends analytics feature coming soon'
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve sales trends: ' . $e->getMessage());
        }
    }

    /**
     * Get performance metrics.
     */
    public function performance(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            
            $todayStats = $this->orderService->getOrdersStatistics($serviceProviderId, 'today');
            $monthStats = $this->orderService->getOrdersStatistics($serviceProviderId, 'month');

            // Calculate performance metrics
            $averageOrderValue = $monthStats['total_orders'] > 0 
                ? $monthStats['total_revenue'] / $monthStats['total_orders'] 
                : 0;

            $orderFulfillmentRate = $monthStats['total_orders'] > 0 
                ? ($monthStats['delivered_orders'] / $monthStats['total_orders']) * 100 
                : 0;

            $orderRejectionRate = $monthStats['total_orders'] > 0 
                ? ($monthStats['rejected_orders'] / $monthStats['total_orders']) * 100 
                : 0;

            return $this->apiResponse->success(
                'Performance metrics retrieved successfully',
                [
                    'today' => [
                        'orders' => $todayStats['total_orders'],
                        'revenue' => $todayStats['total_revenue'],
                    ],
                    'month' => [
                        'orders' => $monthStats['total_orders'],
                        'revenue' => $monthStats['total_revenue'],
                        'average_order_value' => round($averageOrderValue, 2),
                        'fulfillment_rate' => round($orderFulfillmentRate, 2),
                        'rejection_rate' => round($orderRejectionRate, 2),
                    ]
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve performance metrics: ' . $e->getMessage());
        }
    }
}
