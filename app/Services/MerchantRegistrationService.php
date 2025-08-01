<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\SubscriptionPlan;
use App\Models\VerificationCode;
use App\Helpers\ImageHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MerchantRegistrationService
{
    protected OTPService $otpService;

    public function __construct(OTPService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Step 1: Register or update basic information and send OTP
     */
    public function registerBasicInfo(array $data): array
    {
        try {
            DB::beginTransaction();

            // Check if this is an update request (merchant_id provided)
            $isUpdate = isset($data['merchant_id']) && !empty($data['merchant_id']);
            $merchant = null;

            if ($isUpdate) {
                // Find existing merchant for update
                $merchant = Merchant::find($data['merchant_id']);
                if (!$merchant) {
                    return [
                        'success' => false,
                        'message' => __('registration.merchant_not_found'),
                    ];
                }

                // Check if new phone number conflicts with other merchants
                if ($merchant->phone_number !== $data['phone_number']) {
                    if (Merchant::where('phone_number', $data['phone_number'])
                        ->where('id', '!=', $merchant->id)
                        ->exists()) {
                        return [
                            'success' => false,
                            'message' => __('registration.phone_already_registered'),
                        ];
                    }
                }

                // Check if new email conflicts with other merchants
                if (!empty($data['email']) && $merchant->email !== $data['email']) {
                    if (Merchant::where('email', $data['email'])
                        ->where('id', '!=', $merchant->id)
                        ->exists()) {
                        return [
                            'success' => false,
                            'message' => __('validation.unique', ['attribute' => __('attributes.email')]),
                        ];
                    }
                }

                // Update merchant information
                $merchant->update([
                    'name' => [
                        'en' => $data['name_en'],
                        'ar' => $data['name_ar'] ?? $data['name_en'],
                    ],
                    'phone_number' => $data['phone_number'],
                    'email' => $data['email'] ?? null,
                    'preferred_language' => $data['preferred_language'] ?? 'ar',
                    'is_phone_verified' => false, // Reset phone verification if phone changed
                    'phone_verified_at' => null,
                ]);

                $message = __('registration.basic_info_updated_otp_sent');
            } else {
                // Check if phone number already exists for new registration
                if (Merchant::where('phone_number', $data['phone_number'])->exists()) {
                    return [
                        'success' => false,
                        'message' => __('registration.phone_already_registered'),
                    ];
                }

                // Check if email already exists (if provided)
                if (!empty($data['email']) && Merchant::where('email', $data['email'])->exists()) {
                    return [
                        'success' => false,
                        'message' => __('validation.unique', ['attribute' => __('attributes.email')]),
                    ];
                }

                // Create new merchant
                $merchant = Merchant::create([
                    'name' => [
                        'en' => $data['name_en'],
                        'ar' => $data['name_ar'] ?? $data['name_en'],
                    ],
                    'phone_number' => $data['phone_number'],
                    'email' => $data['email'] ?? null,
                    'preferred_language' => $data['preferred_language'] ?? 'ar',
                    'registration_step' => 'basic_info',
                    'status' => 'pending',
                ]);

                $message = __('registration.basic_info_saved_otp_sent');
            }

            // Mark basic info step as completed
            $merchant->completeStep('basic_info', $data);

            // Send OTP immediately (skip registration check since we just created/updated the merchant)
            $otpResult = $this->otpService->sendOTP($data['phone_number'], 'merchant', 'registration', true);

            if (!$otpResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('registration.failed_to_send_otp') . ': ' . $otpResult['message'],
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'merchant' => $merchant,
                    'verification_code' => $otpResult['data']['verification_code'], // Remove in production
                    'expires_at' => $otpResult['data']['expires_at'],
                    'next_step' => 'phone_verification',
                    'is_update' => $isUpdate,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('registration.failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 2: Send phone verification OTP
     */
    public function sendPhoneVerification(string $phoneNumber): array
    {
        $merchant = Merchant::where('phone_number', $phoneNumber)->first();

        if (!$merchant) {
            return [
                'success' => false,
                'message' => __('auth.phone_not_registered'),
            ];
        }

        if ($merchant->is_phone_verified) {
            return [
                'success' => false,
                'message' => __('otp.already_verified'),
            ];
        }

        // Send OTP
        $result = $this->otpService->sendOTP($phoneNumber, 'merchant');

        if ($result['success']) {
            return [
                'success' => true,
                'message' => __('otp.sent_successfully'),
                'data' => [
                    'verification_code' => $result['data']['verification_code'], // Remove in production
                    'expires_at' => $result['data']['expires_at'],
                ],
            ];
        }

        return $result;
    }

    /**
     * Step 2: Verify phone number
     */
    public function verifyPhone(string $phoneNumber, string $code): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::where('phone_number', $phoneNumber)->first();

            if (!$merchant) {
                return [
                    'success' => false,
                    'message' => __('auth.phone_not_registered'),
                ];
            }

            // Verify OTP
            $verificationCode = VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', 'merchant')
                ->where('code', $code)
                ->first();

            if (!$verificationCode) {
                return [
                    'success' => false,
                    'message' => __('otp.invalid_code'),
                ];
            }

            if ($verificationCode->isExpired()) {
                return [
                    'success' => false,
                    'message' => __('otp.expired_code'),
                ];
            }

            // Update merchant
            $merchant->update([
                'is_phone_verified' => true,
                'phone_verified_at' => now(),
            ]);

            // Mark phone verification step as completed
            $merchant->completeStep('phone_verification', ['verified_at' => now()]);

            // Generate authentication token
            $token = $merchant->createToken('merchant-registration-token')->plainTextToken;

            // Delete verification code
            $verificationCode->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => __('merchant.phone_verified_successfully'),
                'data' => [
                    'merchant' => $merchant,
                    'token' => $token,
                    'next_step' => 'subscription',
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('otp.failed_to_verify') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 3: Choose subscription plan
     */
    public function chooseSubscription(int $merchantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);

            if (!$merchant->canProceedToStep('subscription')) {
                return [
                    'success' => false,
                    'message' => __('registration.cannot_proceed_to_step'),
                ];
            }

            $subscriptionPlan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);

            if (!$subscriptionPlan->is_active) {
                return [
                    'success' => false,
                    'message' => __('subscription.plan_inactive'),
                ];
            }

            // Calculate subscription dates
            $startsAt = now();
            $endsAt = $this->calculateSubscriptionEndDate($startsAt, $subscriptionPlan->period);

            // Update merchant subscription
            $merchant->update([
                'subscription_plan_id' => $subscriptionPlan->id,
                'subscription_status' => $subscriptionPlan->price > 0 ? 'pending' : 'active',
                'subscription_starts_at' => $startsAt,
                'subscription_ends_at' => $endsAt,
                'subscription_amount' => $subscriptionPlan->price,
                'is_subscription_paid' => $subscriptionPlan->price == 0,
            ]);

            // If free plan, mark as paid
            if ($subscriptionPlan->price == 0) {
                $merchant->completeStep('subscription', [
                    'plan_id' => $subscriptionPlan->id,
                    'amount' => 0,
                    'payment_method' => 'free',
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $subscriptionPlan->price > 0
                    ? __('subscription.payment_required')
                    : __('subscription.created_successfully'),
                'data' => [
                    'merchant' => $merchant,
                    'subscription_plan' => $subscriptionPlan,
                    'requires_payment' => $subscriptionPlan->price > 0,
                    'next_step' => $subscriptionPlan->price > 0 ? 'payment' : 'business_info',
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('subscription.creation_failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 3: Process payment (mock implementation)
     */
    public function processPayment(int $merchantId, array $paymentData): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);

            if ($merchant->is_subscription_paid) {
                return [
                    'success' => false,
                    'message' => __('subscription.already_paid'),
                ];
            }

            // Mock payment processing
            $paymentSuccess = true; // In real implementation, integrate with payment gateway

            if ($paymentSuccess) {
                $merchant->update([
                    'subscription_status' => 'active',
                    'is_subscription_paid' => true,
                    'payment_method' => $paymentData['payment_method'] ?? 'card',
                    'payment_details' => [
                        'method' => $paymentData['payment_method'] ?? 'card',
                        'last_four' => substr($paymentData['card_number'] ?? '', -4),
                        'processed_at' => now(),
                    ],
                ]);

                $merchant->completeStep('subscription', [
                    'plan_id' => $merchant->subscription_plan_id,
                    'amount' => $merchant->subscription_amount,
                    'payment_method' => $paymentData['payment_method'] ?? 'card',
                    'payment_details' => $merchant->payment_details,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'message' => __('subscription.payment_successful'),
                    'data' => [
                        'merchant' => $merchant,
                        'next_step' => 'business_info',
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('subscription.payment_failed'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('subscription.payment_failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 4: Update business information
     */
    public function updateBusinessInfo(int $merchantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);

            if (!$merchant->canProceedToStep('business_info')) {
                return [
                    'success' => false,
                    'message' => __('registration.cannot_proceed_to_step'),
                ];
            }

            // Handle file uploads using ImageHelper
            $fileFields = ['work_permit', 'id_or_passport', 'health_certificate'];
            $uploadedFiles = [];

            foreach ($fileFields as $field) {
                if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                    $uploadResult = ImageHelper::uploadSingle(
                        $data[$field],
                        'merchant_documents',
                        'public',
                        $merchant->{$field} ?? null
                    );

                    if ($uploadResult['success']) {
                        $uploadedFiles[$field] = $uploadResult['path'];
                        $data[$field] = $uploadResult['path'];
                    } else {
                        throw new \Exception("Failed to upload {$field}: " . $uploadResult['message']);
                    }
                }
            }

            // Update merchant business info
            $merchant->update([
                'business_name' => [
                    'en' => $data['business_name_en'],
                    'ar' => $data['business_name_ar'] ?? $data['business_name_en'],
                ],
                'business_address' => [
                    'en' => $data['business_address_en'] ?? '',
                    'ar' => $data['business_address_ar'] ?? $data['business_address_en'] ?? '',
                ],
                'business_type' => $data['business_type'],
                'commercial_registration_number' => $data['commercial_registration_number'] ?? null,
                'work_permit' => $data['work_permit'] ?? null,
                'id_or_passport' => $data['id_or_passport'] ?? null,
                'health_certificate' => $data['health_certificate'] ?? null,
            ]);

            // Mark business info step as completed
            $merchant->completeStep('business_info', array_merge($data, $uploadedFiles));

            DB::commit();

            return [
                'success' => true,
                'message' => __('business.information_updated'),
                'data' => [
                    'merchant' => $merchant,
                    'next_step' => 'business_profile',
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('business.update_failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 5: Update business profile
     */
    public function updateBusinessProfile(int $merchantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);

            if (!$merchant->canProceedToStep('business_profile')) {
                return [
                    'success' => false,
                    'message' => __('registration.cannot_proceed_to_step'),
                ];
            }

            // Handle logo upload using ImageHelper
            if (isset($data['business_logo']) && $data['business_logo'] instanceof UploadedFile) {
                $uploadResult = ImageHelper::uploadWithResize(
                    $data['business_logo'],
                    'merchant_logos',
                    'logo',
                    'public',
                    $merchant->business_logo
                );

                if ($uploadResult['success']) {
                    $data['business_logo'] = $uploadResult['main_path'];
                } else {
                    throw new \Exception("Failed to upload business logo: " . $uploadResult['message']);
                }
            }

            // Update merchant profile
            $merchant->update([
                'business_logo' => $data['business_logo'] ?? $merchant->business_logo,
                'business_description' => [
                    'en' => $data['business_description_en'] ?? '',
                    'ar' => $data['business_description_ar'] ?? $data['business_description_en'] ?? '',
                ],
                'business_hours' => $data['business_hours'] ?? null,
                'business_phone' => $data['business_phone'] ?? null,
                'business_email' => $data['business_email'] ?? null,
            ]);

            // Mark business profile step as completed
            $merchant->completeStep('business_profile', $data);

            DB::commit();

            return [
                'success' => true,
                'message' => __('business.profile_updated'),
                'data' => [
                    'merchant' => $merchant,
                    'next_step' => 'location',
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('business.profile_update_failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 6: Update location information
     */
    public function updateLocation(int $merchantId, array $data): array
    {
        try {
            DB::beginTransaction();

            $merchant = Merchant::findOrFail($merchantId);

            if (!$merchant->canProceedToStep('location')) {
                return [
                    'success' => false,
                    'message' => __('registration.cannot_proceed_to_step'),
                ];
            }

            // Update merchant location
            $merchant->update([
                'location_latitude' => $data['location_latitude'],
                'location_longitude' => $data['location_longitude'],
                'location_address' => [
                    'en' => $data['location_address_en'] ?? '',
                    'ar' => $data['location_address_ar'] ?? $data['location_address_en'] ?? '',
                ],
                'location_city' => $data['location_city'] ?? null,
                'location_area' => $data['location_area'] ?? null,
                'location_building' => $data['location_building'] ?? null,
                'location_floor' => $data['location_floor'] ?? null,
                'location_notes' => $data['location_notes'] ?? null,
            ]);

            // Mark location step as completed
            $merchant->completeStep('location', $data);

            // Registration is now complete
            $merchant->update([
                'status' => 'active',
                'is_verified' => true,
                'completed_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => __('registration.completed'),
                'data' => [
                    'merchant' => $merchant,
                    'registration_completed' => true,
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('registration.location_update_failed') . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get merchant registration status
     */
    public function getRegistrationStatus(int $merchantId): array
    {
        $merchant = Merchant::with('registrationSteps', 'subscriptionPlan')->findOrFail($merchantId);

        $steps = [
            'basic_info' => $merchant->hasCompletedStep('basic_info'),
            'phone_verification' => $merchant->hasCompletedStep('phone_verification'),
            'subscription' => $merchant->hasCompletedStep('subscription'),
            'business_info' => $merchant->hasCompletedStep('business_info'),
            'business_profile' => $merchant->hasCompletedStep('business_profile'),
            'location' => $merchant->hasCompletedStep('location'),
        ];

        return [
            'success' => true,
            'data' => [
                'merchant' => $merchant,
                'current_step' => $merchant->registration_step,
                'next_step' => $merchant->getNextStep(),
                'completed_steps' => $steps,
                'progress_percentage' => $merchant->getRegistrationProgress(),
                'is_completed' => $merchant->registration_step === 'completed',
            ],
        ];
    }

    /**
     * Calculate subscription end date based on period
     */
    private function calculateSubscriptionEndDate($startDate, string $period): \Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startDate);

        return match ($period) {
            'monthly' => $start->addMonth(),
            'half_year' => $start->addMonths(6),
            'annual' => $start->addYear(),
            default => $start->addMonth(),
        };
    }
}
