<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\ApiResponseService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private ApiResponseService $apiResponse,
        private OrderService $orderService
    ) {}

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $filters = $request->only(['status', 'payment_status', 'date_from', 'date_to', 'search']);
            $paginated = $request->boolean('paginated', true);
            $perPage = $request->integer('per_page', 15);

            $orders = $this->orderService->getOrders($serviceProviderId, $filters, $paginated, $perPage);

            if ($paginated) {
                return $this->apiResponse->success(
                    'Orders retrieved successfully',
                    [
                        'items' => OrderResource::collection($orders->items()),
                        'pagination' => [
                            'total' => $orders->total(),
                            'per_page' => $orders->perPage(),
                            'current_page' => $orders->currentPage(),
                            'last_page' => $orders->lastPage(),
                            'from' => $orders->firstItem(),
                            'to' => $orders->lastItem(),
                        ]
                    ]
                );
            }

            return $this->apiResponse->success(
                'Orders retrieved successfully',
                OrderResource::collection($orders)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve orders: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $order = $this->orderService->getOrder($id, $serviceProviderId);
            
            if (!$order) {
                return $this->apiResponse->error('Order not found', null, 404);
            }

            return $this->apiResponse->success(
                'Order retrieved successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve order: ' . $e->getMessage());
        }
    }

    /**
     * Get pending orders.
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $orders = $this->orderService->getPendingOrders($serviceProviderId);

            return $this->apiResponse->success(
                'Pending orders retrieved successfully',
                OrderResource::collection($orders)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve pending orders: ' . $e->getMessage());
        }
    }

    /**
     * Get active orders.
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            $orders = $this->orderService->getActiveOrders($serviceProviderId);

            return $this->apiResponse->success(
                'Active orders retrieved successfully',
                OrderResource::collection($orders)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve active orders: ' . $e->getMessage());
        }
    }

    /**
     * Confirm an order.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'estimated_minutes' => 'nullable|integer|min:1|max:300'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $estimatedMinutes = $request->integer('estimated_minutes');
            
            $confirmed = $this->orderService->confirmOrder($id, $serviceProviderId, $estimatedMinutes);
            
            if (!$confirmed) {
                return $this->apiResponse->error('Failed to confirm order');
            }

            $order = $this->orderService->getOrder($id, $serviceProviderId);

            return $this->apiResponse->success(
                'Order confirmed successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to confirm order: ' . $e->getMessage());
        }
    }

    /**
     * Reject an order.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $serviceProviderId = $request->user()->id;
            $reason = $request->input('reason');
            
            $rejected = $this->orderService->rejectOrder($id, $serviceProviderId, $reason);
            
            if (!$rejected) {
                return $this->apiResponse->error('Failed to reject order');
            }

            $order = $this->orderService->getOrder($id, $serviceProviderId);

            return $this->apiResponse->success(
                'Order rejected successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to reject order: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as preparing.
     */
    public function preparing(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            
            $updated = $this->orderService->markAsPreparing($id, $serviceProviderId);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to mark order as preparing');
            }

            $order = $this->orderService->getOrder($id, $serviceProviderId);

            return $this->apiResponse->success(
                'Order marked as preparing successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to mark order as preparing: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as ready.
     */
    public function ready(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            
            $updated = $this->orderService->markAsReady($id, $serviceProviderId);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to mark order as ready');
            }

            $order = $this->orderService->getOrder($id, $serviceProviderId);

            return $this->apiResponse->success(
                'Order marked as ready successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to mark order as ready: ' . $e->getMessage());
        }
    }

    /**
     * Mark order as delivered.
     */
    public function delivered(Request $request, int $id): JsonResponse
    {
        try {
            $serviceProviderId = $request->user()->id;
            
            $updated = $this->orderService->markAsDelivered($id, $serviceProviderId);
            
            if (!$updated) {
                return $this->apiResponse->error('Failed to mark order as delivered');
            }

            $order = $this->orderService->getOrder($id, $serviceProviderId);

            return $this->apiResponse->success(
                'Order marked as delivered successfully',
                new OrderResource($order)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to mark order as delivered: ' . $e->getMessage());
        }
    }

    /**
     * Get orders by status.
     */
    public function byStatus(Request $request, string $status): JsonResponse
    {
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled', 'rejected'];
        
        if (!in_array($status, $validStatuses)) {
            return $this->apiResponse->error('Invalid order status', null, 400);
        }

        try {
            $serviceProviderId = $request->user()->id;
            $filters = ['status' => $status];
            $orders = $this->orderService->getOrders($serviceProviderId, $filters);

            return $this->apiResponse->success(
                "Orders with status '{$status}' retrieved successfully",
                OrderResource::collection($orders)
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to retrieve orders: ' . $e->getMessage());
        }
    }
}
