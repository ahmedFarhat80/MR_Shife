<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class SubscriptionPlan extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'price',
        'period',
        'features',
        'is_active',
        'is_popular',
        'sort_order',
        'stripe_price_id',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public $translatable = ['name', 'description', 'features'];

    /**
     * Get all user subscriptions for this plan.
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Scope to get only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get plans ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    /**
     * Get the plan name in the current locale.
     */
    public function getTranslatedNameAttribute()
    {
        return $this->getTranslation('name', app()->getLocale())
            ?: $this->getTranslation('name', 'en')
            ?: 'Unknown Plan';
    }

    /**
     * Get the plan description in the current locale.
     */
    public function getTranslatedDescriptionAttribute()
    {
        return $this->getTranslation('description', app()->getLocale())
            ?: $this->getTranslation('description', 'en');
    }

    /**
     * Get the plan features in the current locale.
     */
    public function getTranslatedFeaturesAttribute()
    {
        return $this->getTranslation('features', app()->getLocale())
            ?: $this->getTranslation('features', 'en')
            ?: [];
    }

    /**
     * Check if this is a free plan.
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return __('subscription.free');
        }
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get period label.
     */
    public function getPeriodLabel(): string
    {
        return match($this->period) {
            'monthly' => __('subscription.per_month'),
            'half_year' => __('subscription.per_six_months'),
            'annual' => __('subscription.per_year'),
            default => __('subscription.per_month'),
        };
    }
}
