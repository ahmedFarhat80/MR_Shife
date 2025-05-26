<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
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
            'name' => [
                'en' => $this->getTranslation('name', 'en'),
                'ar' => $this->getTranslation('name', 'ar'),
                'current' => $this->name,
            ],
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'is_phone_verified' => $this->is_phone_verified,
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'preferred_language' => $this->preferred_language,

            // Subscription Information
            'subscription' => [
                'plan_id' => $this->subscription_plan_id,
                'plan' => $this->whenLoaded('subscriptionPlan', function () {
                    return [
                        'id' => $this->subscriptionPlan->id,
                        'name' => $this->subscriptionPlan->name,
                        'price' => $this->subscriptionPlan->price,
                        'period' => $this->subscriptionPlan->period,
                    ];
                }),
                'status' => $this->subscription_status,
                'starts_at' => $this->subscription_starts_at?->toISOString(),
                'ends_at' => $this->subscription_ends_at?->toISOString(),
                'amount' => $this->subscription_amount,
                'is_paid' => $this->is_subscription_paid,
                'payment_method' => $this->payment_method,
                'has_active_subscription' => $this->hasActiveSubscription(),
            ],

            // Business Information
            'business' => [
                'name' => [
                    'en' => $this->getTranslation('business_name', 'en'),
                    'ar' => $this->getTranslation('business_name', 'ar'),
                    'current' => $this->business_name,
                ],
                'address' => [
                    'en' => $this->getTranslation('business_address', 'en'),
                    'ar' => $this->getTranslation('business_address', 'ar'),
                    'current' => $this->business_address,
                ],
                'type' => $this->business_type,
                'commercial_registration_number' => $this->commercial_registration_number,
                'phone' => $this->business_phone,
                'email' => $this->business_email,

                // Documents
                'documents' => [
                    'work_permit' => $this->work_permit ? [
                        'path' => $this->work_permit,
                        'url' => $this->work_permit_url,
                    ] : null,
                    'id_or_passport' => $this->id_or_passport ? [
                        'path' => $this->id_or_passport,
                        'url' => $this->id_or_passport_url,
                    ] : null,
                    'health_certificate' => $this->health_certificate ? [
                        'path' => $this->health_certificate,
                        'url' => $this->health_certificate_url,
                    ] : null,
                ],
            ],

            // Business Profile
            'profile' => [
                'logo' => $this->business_logo ? [
                    'path' => $this->business_logo,
                    'url' => $this->business_logo_url,
                ] : null,
                'description' => $this->business_description ? [
                    'en' => $this->getTranslation('business_description', 'en'),
                    'ar' => $this->getTranslation('business_description', 'ar'),
                    'current' => $this->business_description,
                ] : null,
                'hours' => $this->business_hours,
                'social_media' => $this->social_media,
            ],

            // Location Information
            'location' => [
                'latitude' => $this->location_latitude,
                'longitude' => $this->location_longitude,
                'address' => $this->location_address ? [
                    'en' => $this->getTranslation('location_address', 'en'),
                    'ar' => $this->getTranslation('location_address', 'ar'),
                    'current' => $this->location_address,
                ] : null,
                'city' => $this->location_city,
                'area' => $this->location_area,
                'building' => $this->location_building,
                'floor' => $this->location_floor,
                'notes' => $this->location_notes,
            ],

            // Registration Information
            'registration' => [
                'step' => $this->registration_step,
                'next_step' => $this->getNextStep(),
                'progress_percentage' => $this->getRegistrationProgress(),
                'is_completed' => $this->registration_step === 'completed',
                'completed_at' => $this->completed_at?->toISOString(),
                'completed_steps' => $this->whenLoaded('registrationSteps', function () {
                    return $this->registrationSteps->mapWithKeys(function ($step) {
                        return [$step->step => $step->is_completed];
                    });
                }),
            ],

            // System Information
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'is_approved' => $this->is_approved,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'settings' => $this->settings,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
