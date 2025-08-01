<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Translatable\HasTranslations;

class Merchant extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasTranslations;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Basic Information
        'name',
        'phone_number',
        'country_code',
        'email',
        'is_phone_verified',
        'phone_verified_at',

        // Subscription Information
        'subscription_plan_id',
        'subscription_status',
        'subscription_starts_at',
        'subscription_ends_at',
        'subscription_amount',
        'payment_method',
        'payment_details',
        'is_subscription_paid',

        // Business Information
        'business_name',
        'business_address',
        'business_type',
        'commercial_registration_number',
        'work_permit',
        'id_or_passport',
        'health_certificate',

        // Business Profile
        'business_logo',
        'business_description',
        'business_hours',
        'business_phone',
        'business_email',
        'social_media',

        // Location Information
        'location_latitude',
        'location_longitude',
        'location_address',
        'location_city',
        'location_area',
        'location_building',
        'location_floor',
        'location_notes',

        // System Fields
        'status',
        'registration_step',
        'is_verified',
        'is_approved',
        'is_featured',
        'preferred_language',
        'last_login_at',
        'last_login_ip',
        'approved_at',
        'completed_at',
        'rejection_reason',
        'settings',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'business_name',
        'business_address',
        'business_description',
        'location_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
        'payment_details',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_phone_verified' => 'boolean',
            'is_subscription_paid' => 'boolean',
            'is_verified' => 'boolean',
            'is_approved' => 'boolean',
            'subscription_amount' => 'decimal:2',
            'location_latitude' => 'decimal:7',
            'location_longitude' => 'decimal:7',
            'payment_details' => 'array',
            'business_hours' => 'array',
            'social_media' => 'array',
            'settings' => 'array',
        ];
    }

    /**
     * Get the subscription plan for this merchant.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get all registration steps for this merchant.
     */
    public function registrationSteps(): HasMany
    {
        return $this->hasMany(MerchantRegistrationStep::class);
    }

    /**
     * Get products belonging to this merchant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get orders for this merchant.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }



    /**
     * Check if merchant has completed a specific registration step.
     */
    public function hasCompletedStep(string $step): bool
    {
        return $this->registrationSteps()
            ->where('step', $step)
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Get the next registration step.
     */
    public function getNextStep(): ?string
    {
        $steps = [
            'basic_info',
            'phone_verification',
            'subscription',
            'business_info',
            'business_profile',
            'location',
            'completed'
        ];

        foreach ($steps as $step) {
            if (!$this->hasCompletedStep($step)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Mark a registration step as completed.
     */
    public function completeStep(string $step, array $stepData = []): void
    {
        $this->registrationSteps()->updateOrCreate(
            ['step' => $step],
            [
                'is_completed' => true,
                'completed_at' => now(),
                'step_data' => $stepData,
            ]
        );

        // Update merchant's current step
        $nextStep = $this->getNextStep();
        $this->update([
            'registration_step' => $nextStep ?? 'completed'
        ]);

        // If all steps completed, mark as completed
        if ($nextStep === null) {
            $this->update([
                'completed_at' => now(),
                'status' => 'active'
            ]);
        }
    }

    /**
     * Check if merchant has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_status === 'active' &&
               $this->subscription_ends_at &&
               $this->subscription_ends_at->isFuture();
    }

    /**
     * Get registration progress percentage.
     */
    public function getRegistrationProgress(): int
    {
        $totalSteps = 6; // excluding 'completed'
        $completedSteps = $this->registrationSteps()
            ->where('is_completed', true)
            ->count();

        return min(100, round(($completedSteps / $totalSteps) * 100));
    }

    /**
     * Check if merchant can proceed to next step.
     */
    public function canProceedToStep(string $step): bool
    {
        $stepOrder = [
            'basic_info' => 1,
            'phone_verification' => 2,
            'subscription' => 3,
            'business_info' => 4,
            'business_profile' => 5,
            'location' => 6,
            'completed' => 7
        ];

        $currentStepOrder = $stepOrder[$this->registration_step] ?? 1;
        $requestedStepOrder = $stepOrder[$step] ?? 1;

        return $requestedStepOrder <= $currentStepOrder + 1;
    }

    /**
     * Get business logo URL.
     */
    public function getBusinessLogoUrlAttribute(): ?string
    {
        return $this->business_logo ? asset('storage/' . $this->business_logo) : null;
    }

    /**
     * Get work permit URL.
     */
    public function getWorkPermitUrlAttribute(): ?string
    {
        return $this->work_permit ? asset('storage/' . $this->work_permit) : null;
    }

    /**
     * Get ID or passport URL.
     */
    public function getIdOrPassportUrlAttribute(): ?string
    {
        return $this->id_or_passport ? asset('storage/' . $this->id_or_passport) : null;
    }

    /**
     * Get health certificate URL.
     */
    public function getHealthCertificateUrlAttribute(): ?string
    {
        return $this->health_certificate ? asset('storage/' . $this->health_certificate) : null;
    }

    /**
     * Get featured products.
     */
    public function featuredProducts()
    {
        return $this->hasMany(Product::class)->where('is_featured', true)->where('is_available', true);
    }

    /**
     * Get popular products.
     */
    public function popularProducts()
    {
        return $this->hasMany(Product::class)->where('is_popular', true)->where('is_available', true);
    }

    /**
     * Get average rating.
     */
    public function getAverageRatingAttribute(): float
    {
        // This would typically come from a reviews table
        return 4.5;
    }

    /**
     * Get reviews count.
     */
    public function getReviewsCountAttribute(): int
    {
        // This would typically come from a reviews table
        return rand(50, 200);
    }

    /**
     * Get orders count.
     */
    public function getOrdersCountAttribute(): int
    {
        // This would typically come from orders table
        return rand(100, 1000);
    }

    /**
     * Get products count.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Get internal categories count.
     */
    public function getInternalCategoriesCountAttribute(): int
    {
        return $this->internalCategories()->count();
    }
}
