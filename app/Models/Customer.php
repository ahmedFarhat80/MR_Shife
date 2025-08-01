<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Translatable\HasTranslations;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone_number',
        'country_code',
        'email',
        'preferred_language',
        'phone_verified',
        'phone_verified_at',
        'email_verified',
        'email_verified_at',
        'avatar',
        'date_of_birth',
        'gender',
        'addresses',
        'default_address',
        'notifications_enabled',
        'sms_notifications',
        'email_notifications',
        'push_notifications',
        'status',
        'last_login_at',
        'last_login_ip',
        'loyalty_points',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified' => 'boolean',
            'phone_verified_at' => 'datetime',
            'email_verified' => 'boolean',
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'addresses' => 'array',
            'default_address' => 'array',
            'notifications_enabled' => 'boolean',
            'sms_notifications' => 'boolean',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'last_login_at' => 'datetime',
            'loyalty_points' => 'integer',
        ];
    }

    /**
     * Get all orders for this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get completed orders only.
     */
    public function completedOrders(): HasMany
    {
        return $this->orders()->where('status', 'delivered');
    }

    /**
     * Get pending orders.
     */
    public function pendingOrders(): HasMany
    {
        return $this->orders()->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery']);
    }

    /**
     * Calculate total number of orders dynamically.
     */
    public function getTotalOrdersAttribute(): int
    {
        return $this->orders()->count();
    }

    /**
     * Calculate total completed orders dynamically.
     */
    public function getCompletedOrdersCountAttribute(): int
    {
        return $this->completedOrders()->count();
    }

    /**
     * Calculate total amount spent dynamically.
     */
    public function getTotalSpentAttribute(): float
    {
        return $this->completedOrders()->sum('total_amount');
    }

    /**
     * Calculate average order value.
     */
    public function getAverageOrderValueAttribute(): float
    {
        $completedOrdersCount = $this->completed_orders_count;

        if ($completedOrdersCount === 0) {
            return 0;
        }

        return $this->total_spent / $completedOrdersCount;
    }

    /**
     * Get customer tier based on total spent.
     */
    public function getCustomerTierAttribute(): string
    {
        $totalSpent = $this->total_spent;

        if ($totalSpent >= 5000) {
            return 'platinum';
        } elseif ($totalSpent >= 2000) {
            return 'gold';
        } elseif ($totalSpent >= 500) {
            return 'silver';
        }

        return 'bronze';
    }

    /**
     * Check if customer is a frequent buyer.
     */
    public function isFrequentBuyer(): bool
    {
        return $this->total_orders >= 10;
    }

    /**
     * Check if customer is verified (both phone and email).
     */
    public function isFullyVerified(): bool
    {
        return $this->phone_verified && $this->email_verified;
    }

    /**
     * Get customer's favorite merchant based on order frequency.
     */
    public function getFavoriteMerchant()
    {
        return $this->orders()
            ->selectRaw('merchant_id, COUNT(*) as order_count')
            ->groupBy('merchant_id')
            ->orderByDesc('order_count')
            ->with('merchant')
            ->first()?->merchant;
    }

    /**
     * Get recent orders (last 30 days).
     */
    public function getRecentOrdersAttribute()
    {
        return $this->orders()
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Calculate loyalty points earned from orders.
     */
    public function calculateEarnedLoyaltyPoints(): int
    {
        // 1 point per 10 SAR spent
        return (int) floor($this->total_spent / 10);
    }

    /**
     * Update loyalty points based on spending.
     */
    public function updateLoyaltyPoints(): void
    {
        $earnedPoints = $this->calculateEarnedLoyaltyPoints();
        $this->update(['loyalty_points' => $earnedPoints]);
    }

    /**
     * Get avatar URL with fallback.
     */
    public function getAvatarUrlAttribute(): string
    {
        // If avatar exists, return full URL
        if ($this->avatar) {
            // Check if file exists
            $fullPath = storage_path('app/public/' . $this->avatar);
            if (file_exists($fullPath)) {
                return config('app.url') . '/storage/' . str_replace('\\', '/', $this->avatar);
            }
        }

        // Generate default avatar with initials
        $name = $this->getTranslation('name', 'en') ?: $this->getTranslation('name', 'ar') ?: 'Customer';
        $initials = collect(explode(' ', $name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->join('') ?: 'C';

        return "https://ui-avatars.com/api/?name={$initials}&background=random&color=fff&size=200";
    }

    /**
     * Scope for active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified customers.
     */
    public function scopeVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    /**
     * Scope for customers with recent activity.
     */
    public function scopeRecentlyActive($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Get the customer's search history.
     */
    public function searchHistory(): HasMany
    {
        return $this->hasMany(SearchHistory::class);
    }
}
