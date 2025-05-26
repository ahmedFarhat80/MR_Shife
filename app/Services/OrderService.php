<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Get orders for service provider.
     *
     * @param int $serviceProviderId
     * @param array $filters
     * @param bool $paginated
     * @param int $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getOrders(int $serviceProviderId, array $filters = [], bool $paginated = false, int $perPage = 15)
    {
        if ($paginated) {
            return $this->orderRepository->getPaginatedByServiceProvider($serviceProviderId, $filters, $perPage);
        }

        return $this->orderRepository->getByServiceProvider($serviceProviderId, $filters);
    }

    /**
     * Get a single order.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @return Order|null
     */
    public function getOrder(int $orderId, int $serviceProviderId): ?Order
    {
        $order = $this->orderRepository->find($orderId);
        if (!$order || $order->merchant_id !== $merchantId) {
            return null;
        }

        return $order->load(['customer', 'items.product']);
    }

    /**
     * Get pending orders.
     *
     * @param int $serviceProviderId
     * @return Collection
     */
    public function getPendingOrders(int $serviceProviderId): Collection
    {
        return $this->orderRepository->getPendingOrders($serviceProviderId);
    }

    /**
     * Get active orders.
     *
     * @param int $serviceProviderId
     * @return Collection
     */
    public function getActiveOrders(int $serviceProviderId): Collection
    {
        return $this->orderRepository->getActiveOrders($serviceProviderId);
    }

    /**
     * Confirm an order.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @param int|null $estimatedMinutes
     * @return bool
     * @throws \Exception
     */
    public function confirmOrder(int $orderId, int $serviceProviderId, ?int $estimatedMinutes = null): bool
    {
        $order = $this->getOrder($orderId, $serviceProviderId);
        if (!$order) {
            throw new \Exception('Order not found or does not belong to this service provider.');
        }

        if (!$order->canBeConfirmed()) {
            throw new \Exception('Order cannot be confirmed in its current status.');
        }

        $additionalData = [];
        if ($estimatedMinutes) {
            $additionalData['estimated_delivery_time'] = now()->addMinutes($estimatedMinutes);
        }

        return $this->orderRepository->updateStatus($orderId, 'confirmed', $additionalData);
    }

    /**
     * Reject an order.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function rejectOrder(int $orderId, int $serviceProviderId, string $reason): bool
    {
        $order = $this->getOrder($orderId, $serviceProviderId);
        if (!$order) {
            throw new \Exception('Order not found or does not belong to this service provider.');
        }

        if (!$order->canBeRejected()) {
            throw new \Exception('Order cannot be rejected in its current status.');
        }

        return $this->orderRepository->updateStatus($orderId, 'rejected', [
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Mark order as preparing.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @return bool
     * @throws \Exception
     */
    public function markAsPreparing(int $orderId, int $serviceProviderId): bool
    {
        $order = $this->getOrder($orderId, $serviceProviderId);
        if (!$order) {
            throw new \Exception('Order not found or does not belong to this service provider.');
        }

        if (!$order->canBePreparing()) {
            throw new \Exception('Order cannot be marked as preparing in its current status.');
        }

        return $this->orderRepository->updateStatus($orderId, 'preparing');
    }

    /**
     * Mark order as ready.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @return bool
     * @throws \Exception
     */
    public function markAsReady(int $orderId, int $serviceProviderId): bool
    {
        $order = $this->getOrder($orderId, $serviceProviderId);
        if (!$order) {
            throw new \Exception('Order not found or does not belong to this service provider.');
        }

        if (!$order->canBeReady()) {
            throw new \Exception('Order cannot be marked as ready in its current status.');
        }

        return $this->orderRepository->updateStatus($orderId, 'ready');
    }

    /**
     * Mark order as delivered.
     *
     * @param int $orderId
     * @param int $serviceProviderId
     * @return bool
     * @throws \Exception
     */
    public function markAsDelivered(int $orderId, int $serviceProviderId): bool
    {
        $order = $this->getOrder($orderId, $serviceProviderId);
        if (!$order) {
            throw new \Exception('Order not found or does not belong to this service provider.');
        }

        if (!$order->canBeDelivered()) {
            throw new \Exception('Order cannot be marked as delivered in its current status.');
        }

        return $this->orderRepository->updateStatus($orderId, 'delivered');
    }

    /**
     * Get orders statistics.
     *
     * @param int $serviceProviderId
     * @param string $period
     * @return array
     */
    public function getOrdersStatistics(int $serviceProviderId, string $period = 'today'): array
    {
        return $this->orderRepository->getOrdersStatistics($serviceProviderId, $period);
    }

    /**
     * Get revenue statistics.
     *
     * @param int $serviceProviderId
     * @param string $period
     * @return array
     */
    public function getRevenueStatistics(int $serviceProviderId, string $period = 'month'): array
    {
        return $this->orderRepository->getRevenueStatistics($serviceProviderId, $period);
    }

    /**
     * Get dashboard statistics.
     *
     * @param int $serviceProviderId
     * @return array
     */
    public function getDashboardStatistics(int $serviceProviderId): array
    {
        $todayStats = $this->getOrdersStatistics($serviceProviderId, 'today');
        $monthStats = $this->getOrdersStatistics($serviceProviderId, 'month');
        $revenueStats = $this->getRevenueStatistics($serviceProviderId, 'month');

        return [
            'today' => $todayStats,
            'month' => $monthStats,
            'revenue' => $revenueStats,
            'pending_orders_count' => $this->getPendingOrders($serviceProviderId)->count(),
            'active_orders_count' => $this->getActiveOrders($serviceProviderId)->count(),
        ];
    }
}
