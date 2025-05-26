<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantRegistrationStep extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'merchant_registration_steps';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'merchant_id',
        'step',
        'is_completed',
        'completed_at',
        'step_data',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'step_data' => 'array',
        ];
    }

    /**
     * Get the merchant that owns this registration step.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get step display name.
     */
    public function getStepDisplayNameAttribute(): string
    {
        $stepNames = [
            'basic_info' => __('registration.steps.basic_info'),
            'phone_verification' => __('registration.steps.phone_verification'),
            'subscription' => __('registration.steps.subscription'),
            'business_info' => __('registration.steps.business_info'),
            'business_profile' => __('registration.steps.business_profile'),
            'location' => __('registration.steps.location'),
            'completed' => __('registration.steps.completed'),
        ];

        return $stepNames[$this->step] ?? $this->step;
    }

    /**
     * Get step order number.
     */
    public function getStepOrderAttribute(): int
    {
        $stepOrder = [
            'basic_info' => 1,
            'phone_verification' => 2,
            'subscription' => 3,
            'business_info' => 4,
            'business_profile' => 5,
            'location' => 6,
            'completed' => 7,
        ];

        return $stepOrder[$this->step] ?? 0;
    }
} 