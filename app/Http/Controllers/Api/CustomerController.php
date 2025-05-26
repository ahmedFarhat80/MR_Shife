<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected CustomerAnalyticsService $analyticsService;

    public function __construct(CustomerAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get customer profile with calculated statistics.
     */
    public function profile(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'message' => __('api.customer_profile_retrieved'),
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Get customer dashboard with analytics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $customer = $request->user();
        $dashboard = $this->analyticsService->getCustomerDashboard($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.dashboard_data_retrieved'),
            'data' => $dashboard,
        ]);
    }

    /**
     * Get customer overview statistics.
     */
    public function overview(Request $request): JsonResponse
    {
        $customer = $request->user();
        $overview = $this->analyticsService->getCustomerOverview($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.overview_retrieved'),
            'data' => $overview,
        ]);
    }

    /**
     * Get customer spending analytics.
     */
    public function spendingAnalytics(Request $request): JsonResponse
    {
        $customer = $request->user();
        $analytics = $this->analyticsService->getSpendingAnalytics($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.spending_analytics_retrieved'),
            'data' => $analytics,
        ]);
    }

    /**
     * Get customer order patterns.
     */
    public function orderPatterns(Request $request): JsonResponse
    {
        $customer = $request->user();
        $patterns = $this->analyticsService->getOrderPatterns($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.order_patterns_retrieved'),
            'data' => $patterns,
        ]);
    }

    /**
     * Get customer loyalty status.
     */
    public function loyaltyStatus(Request $request): JsonResponse
    {
        $customer = $request->user();
        $loyaltyStatus = $this->analyticsService->getLoyaltyStatus($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.loyalty_status_retrieved'),
            'data' => $loyaltyStatus,
        ]);
    }

    /**
     * Update loyalty points based on current spending.
     */
    public function updateLoyaltyPoints(Request $request): JsonResponse
    {
        $customer = $request->user();
        $customer->updateLoyaltyPoints();

        return response()->json([
            'success' => true,
            'message' => __('api.loyalty_points_updated'),
            'data' => [
                'loyalty_points' => $customer->fresh()->loyalty_points,
                'customer_tier' => $customer->customer_tier,
            ],
        ]);
    }

    /**
     * Get customer statistics summary.
     */
    public function statistics(Request $request): JsonResponse
    {
        $customer = $request->user();

        $statistics = [
            'basic_stats' => [
                'total_orders' => $customer->total_orders,
                'completed_orders' => $customer->completed_orders_count,
                'total_spent' => $customer->total_spent,
                'average_order_value' => $customer->average_order_value,
                'loyalty_points' => $customer->loyalty_points,
            ],
            'tier_info' => [
                'current_tier' => $customer->customer_tier,
                'is_frequent_buyer' => $customer->isFrequentBuyer(),
                'account_age_days' => $customer->created_at->diffInDays(now()),
            ],
            'recent_activity' => [
                'recent_orders_count' => $customer->recent_orders->count(),
                'last_order_date' => $customer->orders()->latest()->first()?->created_at?->format('Y-m-d H:i:s'),
                'last_login' => $customer->last_login_at?->format('Y-m-d H:i:s'),
            ],
            'favorite_merchant' => $customer->getFavoriteMerchant() ? [
                'id' => $customer->getFavoriteMerchant()->id,
                'name' => $customer->getFavoriteMerchant()->getTranslations('name'),
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'message' => __('api.customer_statistics_retrieved'),
            'data' => $statistics,
        ]);
    }

    /**
     * Get all customers with their calculated statistics (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $status = $request->get('status');
        $tier = $request->get('tier');

        $query = Customer::query();

        // Search by name, phone, or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by tier (calculated dynamically)
        if ($tier) {
            // This is more complex since tier is calculated
            // We'll need to filter based on total_spent ranges
            $tierRanges = [
                'bronze' => [0, 499.99],
                'silver' => [500, 1999.99],
                'gold' => [2000, 4999.99],
                'platinum' => [5000, PHP_FLOAT_MAX],
            ];

            if (isset($tierRanges[$tier])) {
                [$min, $max] = $tierRanges[$tier];
                
                $query->whereHas('orders', function ($q) use ($min, $max) {
                    $q->where('status', 'delivered')
                      ->havingRaw('SUM(total_amount) >= ? AND SUM(total_amount) <= ?', [$min, $max]);
                }, '>=', 1);
            }
        }

        $customers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => __('api.customers_retrieved'),
            'data' => CustomerResource::collection($customers),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Get specific customer details (Admin only).
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => __('api.customer_details_retrieved'),
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Get customer analytics dashboard (Admin only).
     */
    public function adminDashboard(Customer $customer): JsonResponse
    {
        $dashboard = $this->analyticsService->getCustomerDashboard($customer);

        return response()->json([
            'success' => true,
            'message' => __('api.customer_analytics_retrieved'),
            'data' => $dashboard,
        ]);
    }
} 