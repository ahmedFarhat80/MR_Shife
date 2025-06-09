<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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

            // Basic Information
            'name' => $this->getTranslations('name'),
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'preferred_language' => $this->preferred_language,

            // Verification Status
            'phone_verified' => $this->phone_verified,
            'phone_verified_at' => $this->phone_verified_at?->format('Y-m-d H:i:s'),
            'email_verified' => $this->email_verified,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'is_fully_verified' => $this->isFullyVerified(),

            // Profile Information
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,

            // Address Information
            'addresses' => $this->addresses,
            'default_address' => $this->default_address,

            // Preferences
            'notifications_enabled' => $this->notifications_enabled,
            'sms_notifications' => $this->sms_notifications,
            'email_notifications' => $this->email_notifications,

            // Account Status
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->format('Y-m-d H:i:s'),
            'last_login_ip' => $this->last_login_ip,

            // Loyalty & Statistics (Calculated Dynamically)
            'loyalty_points' => $this->loyalty_points,
            'total_orders' => $this->total_orders, // Calculated dynamically
            'completed_orders_count' => $this->completed_orders_count, // Calculated dynamically
            'total_spent' => number_format($this->total_spent, 2), // Calculated dynamically
            'average_order_value' => number_format($this->average_order_value, 2), // Calculated dynamically
            'customer_tier' => $this->customer_tier, // Calculated dynamically
            'is_frequent_buyer' => $this->isFrequentBuyer(),

            // Additional Insights
            'favorite_merchant' => $this->whenLoaded('favoriteMerchant', function () {
                return [
                    'id' => $this->getFavoriteMerchant()?->id,
                    'name' => $this->getFavoriteMerchant()?->getTranslations('name'),
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

            // Recent Activity Summary
            'recent_orders_count' => $this->recent_orders->count(),
            'last_order_date' => $this->orders()->latest()->first()?->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
