<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
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
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'amount_paid' => $this->amount_paid,
            'formatted_amount_paid' => $this->getFormattedAmountPaid(),
            'currency' => $this->currency,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'cancelled_at' => $this->cancelled_at,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_cancelled' => $this->isCancelled(),
            'days_remaining' => $this->daysRemaining(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
