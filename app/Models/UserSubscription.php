<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'subscription_plan_id',
        'status',
        'amount_paid',
        'currency',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'payment_details',
        'payment_method',
        'transaction_id',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_details' => 'array',
    ];

    /**
     * Get the user that owns the subscription (polymorphic).
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where('ends_at', '>', now());
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<=', now());
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at <= now()
            && $this->ends_at > now();
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->ends_at <= now();
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get days remaining in subscription.
     */
    public function daysRemaining(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->ends_at);
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): bool
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Activate the subscription.
     */
    public function activate(): bool
    {
        $this->update([
            'status' => 'active',
            'cancelled_at' => null,
        ]);

        return true;
    }

    /**
     * Extend subscription by given period.
     */
    public function extend(string $period): bool
    {
        $endDate = $this->ends_at;

        switch ($period) {
            case 'monthly':
                $newEndDate = $endDate->addMonth();
                break;
            case 'half_year':
                $newEndDate = $endDate->addMonths(6);
                break;
            case 'annual':
                $newEndDate = $endDate->addYear();
                break;
            default:
                return false;
        }

        $this->update(['ends_at' => $newEndDate]);
        return true;
    }

    /**
     * Get formatted amount paid.
     */
    public function getFormattedAmountPaid(): string
    {
        return '$' . number_format($this->amount_paid, 2);
    }

    /**
     * Get subscription status label.
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => __('subscription.status.active'),
            'cancelled' => __('subscription.status.cancelled'),
            'expired' => __('subscription.status.expired'),
            'pending' => __('subscription.status.pending'),
            default => __('subscription.status.unknown'),
        };
    }
}
