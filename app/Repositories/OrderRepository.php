<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class OrderRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     * Get orders by merchant with filters.
     *
     * @param int $merchantId
     * @param array $filters
     * @param bool $paginated
     * @param int $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getByMerchant(int $merchantId, array $filters = [], bool $paginated = false, int $perPage = 15)
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->with(['customer', 'items.product']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name->en', 'like', "%{$search}%")
                        ->orWhere('name->ar', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        $query->orderBy('created_at', 'desc');

        return $paginated ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Get orders by customer.
     *
     * @param int $customerId
     * @param array $filters
     * @param bool $paginated
     * @param int $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getByCustomer(int $customerId, array $filters = [], bool $paginated = false, int $perPage = 15)
    {
        $query = $this->model->where('customer_id', $customerId)
            ->with(['merchant', 'items.product']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $query->orderBy('created_at', 'desc');

        return $paginated ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Get pending orders for merchant.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getPendingByMerchant(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get active orders for merchant.
     *
     * @param int $merchantId
     * @return Collection
     */
    public function getActiveByMerchant(int $merchantId): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->whereIn('status', ['confirmed', 'preparing', 'ready', 'out_for_delivery'])
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get orders by status for merchant.
     *
     * @param int $merchantId
     * @param string $status
     * @return Collection
     */
    public function getByStatusForMerchant(int $merchantId, string $status): Collection
    {
        return $this->model->where('merchant_id', $merchantId)
            ->where('status', $status)
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get order statistics for merchant.
     *
     * @param int $merchantId
     * @param string $period
     * @return array
     */
    public function getStatistics(int $merchantId, string $period = 'today'): array
    {
        $query = $this->model->where('merchant_id', $merchantId);

        // Apply period filter
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        return [
            'total_orders' => $query->count(),
            'pending_orders' => (clone $query)->where('status', 'pending')->count(),
            'completed_orders' => (clone $query)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $query)->whereIn('status', ['cancelled', 'rejected'])->count(),
            'total_revenue' => (clone $query)->where('status', 'delivered')->sum('total_amount'),
            'average_order_value' => (clone $query)->where('status', 'delivered')->avg('total_amount'),
        ];
    }

    /**
     * Get revenue data for merchant.
     *
     * @param int $merchantId
     * @param string $period
     * @param string $groupBy
     * @return Collection
     */
    public function getRevenueData(int $merchantId, string $period = 'month', string $groupBy = 'day'): Collection
    {
        $query = $this->model->where('merchant_id', $merchantId)
            ->where('status', 'delivered')
            ->where('payment_status', 'paid');

        // Apply period filter
        switch ($period) {
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        // Group by period
        switch ($groupBy) {
            case 'day':
                return $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            case 'week':
                return $query->selectRaw('WEEK(created_at) as week, SUM(total_amount) as revenue, COUNT(*) as orders')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();
            case 'month':
                return $query->selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue, COUNT(*) as orders')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
        }

        return collect();
    }
}
