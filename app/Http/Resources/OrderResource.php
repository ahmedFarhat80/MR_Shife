<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => [
                'value' => $this->status,
                'label' => $this->status_label,
                'color' => $this->status_color,
            ],
            'payment' => [
                'status' => $this->payment_status,
                'method' => $this->payment_method,
            ],
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'pricing' => [
                'subtotal' => [
                    'amount' => (float) $this->subtotal,
                    'formatted' => number_format($this->subtotal, 2),
                ],
                'tax_amount' => [
                    'amount' => (float) $this->tax_amount,
                    'formatted' => number_format($this->tax_amount, 2),
                ],
                'delivery_fee' => [
                    'amount' => (float) $this->delivery_fee,
                    'formatted' => number_format($this->delivery_fee, 2),
                ],
                'service_fee' => [
                    'amount' => (float) $this->service_fee,
                    'formatted' => number_format($this->service_fee, 2),
                ],
                'discount_amount' => [
                    'amount' => (float) $this->discount_amount,
                    'formatted' => number_format($this->discount_amount, 2),
                ],
                'total_amount' => [
                    'amount' => (float) $this->total_amount,
                    'formatted' => number_format($this->total_amount, 2),
                ],
                'currency' => 'USD',
            ],
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'rejection_reason' => $this->when($this->status === 'rejected', $this->rejection_reason),
            'timestamps' => [
                'created_at' => $this->created_at?->toISOString(),
                'confirmed_at' => $this->confirmed_at?->toISOString(),
                'prepared_at' => $this->prepared_at?->toISOString(),
                'delivered_at' => $this->delivered_at?->toISOString(),
                'estimated_delivery_time' => $this->estimated_delivery_time?->toISOString(),
            ],
            'actions' => [
                'can_confirm' => $this->canBeConfirmed(),
                'can_reject' => $this->canBeRejected(),
                'can_prepare' => $this->canBePreparing(),
                'can_ready' => $this->canBeReady(),
                'can_deliver' => $this->canBeDelivered(),
                'can_cancel' => $this->canBeCancelled(),
            ],
        ];
    }
}
