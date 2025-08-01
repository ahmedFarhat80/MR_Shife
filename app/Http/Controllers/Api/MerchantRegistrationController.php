<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\SubscriptionPlan;
use App\Models\VerificationCode;
use App\Services\ApiResponseService;
use App\Services\MerchantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MerchantRegistrationController extends Controller
{
    protected ApiResponseService $apiResponse;
    protected MerchantRegistrationService $registrationService;

    public function __construct(
        ApiResponseService $apiResponse,
        MerchantRegistrationService $registrationService
    ) {
        $this->apiResponse = $apiResponse;
        $this->registrationService = $registrationService;
    }

    /**
     * Register vendor (matches new_signup_screen.dart vendor flow)
     */
    public function register(Request $request)
    {
        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        $validator = Validator::make($request->all(), [
            'english_full_name' => 'required|string|max:255',
            'arabic_full_name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{9}$/', // Saudi mobile: 9 digits only
                Rule::unique('merchants')->where(function ($query) use ($phoneNumber, $countryCode) {
                    return $query->where('phone_number', $phoneNumber)
                                 ->where('country_code', $countryCode);
                }),
            ],
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/', // Optional country code like +966
            'email' => 'nullable|email|unique:merchants,email',
            'agree_to_terms' => 'required|boolean|accepted',
        ], [
            'english_full_name.required' => 'English name is required for vendors',
            'arabic_full_name.required' => 'Arabic name is required for vendors',
            'phone_number.required' => 'Phone number is required',
            'phone_number.regex' => 'Phone number must be 9 digits (Saudi format)',
            'phone_number.unique' => 'Phone number already registered with this country code',
            'country_code.regex' => 'Country code must be in format +XXX',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'Email already registered',
            'agree_to_terms.accepted' => 'You must agree to terms and conditions',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {

            DB::beginTransaction();

            // Create merchant
            $merchant = Merchant::create([
                'name' => [
                    'en' => $request->english_full_name,
                    'ar' => $request->arabic_full_name,
                ],
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'email' => $request->email,
                'preferred_language' => 'en',
                'status' => 'pending',
                'registration_step' => 'basic_info',
            ]);

            // Send 4-digit OTP (matching the app)
            $otpCode = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            VerificationCode::create([
                'phone_number' => $phoneNumber, // Store only the phone number without country code
                'code' => $otpCode,
                'type' => 'merchant',
                'expires_at' => now()->addMinutes(1), // 1 minute expiry
            ]);

            DB::commit();

            return $this->apiResponse->success(
                'Registration successful. OTP sent to your phone.',
                [
                    'merchant' => new MerchantResource($merchant),
                    'verification_code' => $otpCode, // Remove in production
                    'expires_at' => now()->addMinutes(1)->toISOString(),
                    'next_step' => 'otp_verification',
                ],
                [],
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error('Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify OTP for merchant (matches otp_verification_screen.dart)
     */
    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
            'otp' => 'required|string|size:4', // 4 digits as per app
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            // Get country code (default to +966 for Saudi Arabia)
            $countryCode = $request->country_code ?? '+966';
            $phoneNumber = $request->phone_number;
            $fullPhoneNumber = $countryCode . $phoneNumber;

            // Find verification code (stored with full phone number)
            $verification = VerificationCode::where('phone_number', $fullPhoneNumber)
                ->where('type', 'merchant')
                ->where('code', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verification) {
                return $this->apiResponse->error('Invalid or expired OTP');
            }

            DB::beginTransaction();

            // Delete verification code
            $verification->delete();

            // Update merchant status and create token (search by phone_number and country_code)
            $merchant = Merchant::where('phone_number', $phoneNumber)
                ->where('country_code', $countryCode)
                ->first();
            $merchant->update([
                'is_phone_verified' => true,
                'phone_verified_at' => now(),
                'status' => 'pending',
                'registration_step' => 'subscription', // Next step: subscription plans
            ]);

            $token = $merchant->createToken('merchant-token', ['merchant'])->plainTextToken;

            DB::commit();

            return $this->apiResponse->success(
                'Phone verified successfully',
                [
                    'merchant' => new MerchantResource($merchant),
                    'token' => $token,
                    'next_step' => 'vendor_onboarding_step1', // Go to subscription plans
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error('Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Resend OTP for merchant registration
     */
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            // Get country code (default to +966 for Saudi Arabia)
            $countryCode = $request->country_code ?? '+966';
            $phoneNumber = $request->phone_number;

            // Generate new 4-digit OTP
            $otpCode = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            // Delete old verification codes
            VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', 'merchant')
                ->delete();

            // Create new verification code
            VerificationCode::create([
                'phone_number' => $phoneNumber,
                'code' => $otpCode,
                'type' => 'merchant',
                'expires_at' => now()->addMinutes(1),
            ]);

            return $this->apiResponse->success(
                'OTP resent successfully',
                [
                    'verification_code' => $otpCode, // Remove in production
                    'expires_at' => now()->addMinutes(1)->toISOString(),
                ]
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to resend OTP: ' . $e->getMessage());
        }
    }

    /**
     * Get subscription plans (matches vendor_step1_screen.dart)
     */
    public function getSubscriptionPlans()
    {
        try {
            // Get subscription plans from database
            $dbPlans = SubscriptionPlan::active()
                ->ordered()
                ->get();

            // Transform plans to match Flutter app format
            $plans = $dbPlans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->getTranslation('name', 'en') ?: $plan->name,
                    'price' => $plan->getFormattedPrice(),
                    'period' => $plan->getPeriodLabel(),
                    'duration_months' => match($plan->period) {
                        'monthly' => 1,
                        'half_year' => 6,
                        'annual' => 12,
                        default => 1,
                    },
                    'is_recommended' => $plan->is_popular,
                    'is_free' => $plan->isFree(),
                    'benefits' => $plan->getTranslation('features', 'en') ?: $plan->features ?: [
                        'Save unlimited notes to a single project',
                        'Create unlimited projects and teams',
                        'Daily backups to keep your data safe'
                    ]
                ];
            });

            return $this->apiResponse->success(
                'Subscription plans retrieved successfully',
                [
                    'plans' => $plans,
                    'current_step' => 1,
                    'total_steps' => 4,
                ]
            );

        } catch (\Exception $e) {
            return $this->apiResponse->error('Failed to get subscription plans: ' . $e->getMessage());
        }
    }



    /**
     * Step 2: Send phone verification OTP
     */
    public function sendPhoneVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:merchants,phone_number',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $result = $this->registrationService->sendPhoneVerification($request->phone_number);

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'verification_code' => $result['data']['verification_code'], // Remove in production
                'expires_at' => $result['data']['expires_at'],
            ]
        );
    }

    /**
     * Step 2: Verify phone number
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:merchants,phone_number',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $result = $this->registrationService->verifyPhone(
            $request->phone_number,
            $request->code
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'token' => $result['data']['token'],
                'next_step' => $result['data']['next_step'],
            ]
        );
    }



    /**
     * Step 1: Choose subscription plan (matches vendor_step1_screen.dart)
     */
    public function chooseSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $merchant = $request->user();
            if (!$merchant) {
                return $this->apiResponse->error('Unauthorized. Merchant access required.', [], 401);
            }

            // Get the selected subscription plan
            $plan = SubscriptionPlan::findOrFail($request->plan_id);

            DB::beginTransaction();

            // Update merchant with selected plan
            $merchant->update([
                'subscription_plan_id' => $plan->id,
                'registration_step' => 'business_info', // Next step: business information
            ]);

            // If it's a free plan, mark as paid automatically
            if ($plan->isFree()) {
                $merchant->update([
                    'subscription_status' => 'active',
                    'is_subscription_paid' => true,
                    'subscription_starts_at' => now(),
                    'subscription_ends_at' => now()->addYear(), // Free plans get 1 year
                ]);
            } else {
                $merchant->update([
                    'subscription_status' => 'pending',
                    'is_subscription_paid' => false,
                ]);
            }

            DB::commit();

            return $this->apiResponse->success(
                'Subscription plan selected successfully',
                [
                    'merchant' => new MerchantResource($merchant),
                    'subscription_plan' => [
                        'id' => $plan->id,
                        'name' => $plan->getTranslation('name', 'en'),
                        'price' => $plan->getFormattedPrice(),
                        'is_free' => $plan->isFree(),
                    ],
                    'requires_payment' => !$plan->isFree(),
                    'next_step' => 'vendor_step2', // Go to business information
                    'current_step' => 2,
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error('Failed to select plan: ' . $e->getMessage());
        }
    }

    /**
     * Step 3: Process payment
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:card,paypal,bank_transfer',
            'card_number' => 'required_if:payment_method,card|string',
            'expiry_month' => 'required_if:payment_method,card|integer|between:1,12',
            'expiry_year' => 'required_if:payment_method,card|integer|min:' . date('Y'),
            'cvv' => 'required_if:payment_method,card|string|size:3',
            'cardholder_name' => 'required_if:payment_method,card|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        $result = $this->registrationService->processPayment(
            $merchant->id,
            $request->all()
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'next_step' => $result['data']['next_step'],
                'payment_notice' => __('subscription.mock_payment_notice'),
            ]
        );
    }

    /**
     * Step 4: Update business information
     */
    public function updateBusinessInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name_en' => 'required|string|max:255',
            'business_name_ar' => 'nullable|string|max:255',
            'business_address_en' => 'nullable|string',
            'business_address_ar' => 'nullable|string',
            'business_type' => 'required|string|max:100',
            'commercial_registration_number' => 'required|string|max:100',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email',

            // Required documents (PDF files only as per mobile app)
            'work_permit' => 'required|file|mimes:pdf|max:5120', // 5MB max
            'id_or_passport' => 'required|file|mimes:pdf|max:5120', // 5MB max
            'health_certificate' => 'required|file|mimes:pdf|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        $result = $this->registrationService->updateBusinessInfo(
            $merchant->id,
            $request->all()
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        // Auto-complete onboarding after business info
        $merchant = $result['data']['merchant'];
        $merchant->update([
            'status' => 'pending', // Merchant needs admin approval before becoming active
            'registration_step' => 'completed',
            'completed_at' => now(),
        ]);

        return $this->apiResponse->success(
            'Business information updated and onboarding completed successfully! Your account will be reviewed.',
            [
                'merchant' => new MerchantResource($merchant->fresh()),
                'next_step' => 'home', // Can now access merchant dashboard
                'status' => 'pending',
                'message' => 'Your merchant account is now complete and pending admin approval.',
            ]
        );
    }

    /**
     * Step 5: Update business profile
     */
    public function updateBusinessProfile(Request $request)
    {
        // Parse JSON strings to arrays if needed
        $data = $request->all();

        // Handle business_hours JSON string
        if (isset($data['business_hours']) && is_string($data['business_hours'])) {
            $businessHours = json_decode($data['business_hours'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['business_hours'] = $businessHours;
            }
        }



        $validator = Validator::make($data, [
            'business_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_description_en' => 'nullable|string|max:1000',
            'business_description_ar' => 'nullable|string|max:1000',
            'business_hours' => 'nullable|array',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        // Use the parsed data instead of $request->all()
        $result = $this->registrationService->updateBusinessProfile(
            $merchant->id,
            array_merge($request->all(), $data)
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'next_step' => $result['data']['next_step'],
            ]
        );
    }

    /**
     * Step 6: Update location information
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_latitude' => 'required|numeric|between:-90,90',
            'location_longitude' => 'required|numeric|between:-180,180',
            'location_address_en' => 'nullable|string',
            'location_address_ar' => 'nullable|string',
            'location_city' => 'nullable|string|max:100',
            'location_area' => 'nullable|string|max:100',
            'location_building' => 'nullable|string|max:100',
            'location_floor' => 'nullable|string|max:50',
            'location_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        $result = $this->registrationService->updateLocation(
            $merchant->id,
            $request->all()
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'registration_completed' => $result['data']['registration_completed'],
            ]
        );
    }

    /**
     * Get registration status
     */
    public function getRegistrationStatus(Request $request)
    {
        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        $result = $this->registrationService->getRegistrationStatus($merchant->id);

        return $this->apiResponse->success(
            __('api.data_retrieved'),
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'current_step' => $result['data']['current_step'],
                'next_step' => $result['data']['next_step'],
                'completed_steps' => $result['data']['completed_steps'],
                'progress_percentage' => $result['data']['progress_percentage'],
                'is_completed' => $result['data']['is_completed'],
            ]
        );
    }

    /**
     * Step 4: Complete onboarding with location information
     */
    public function completeOnboarding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location_latitude' => 'required|numeric|between:-90,90',
            'location_longitude' => 'required|numeric|between:-180,180',
            'location_address_en' => 'required|string|max:500',
            'location_address_ar' => 'required|string|max:500',
            'location_city' => 'required|string|max:100',
            'location_area' => 'required|string|max:100',
            'location_building' => 'nullable|string|max:100',
            'location_floor' => 'nullable|string|max:50',
            'location_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $merchant = auth('sanctum')->user();

            DB::beginTransaction();

            // Update location information
            $merchant->update([
                'location_latitude' => $request->location_latitude,
                'location_longitude' => $request->location_longitude,
                'location_address' => [
                    'en' => $request->location_address_en,
                    'ar' => $request->location_address_ar,
                ],
                'location_city' => $request->location_city,
                'location_area' => $request->location_area,
                'location_building' => $request->location_building,
                'location_floor' => $request->location_floor,
                'location_notes' => $request->location_notes,
                'registration_step' => 'completed',
                'completed_at' => now(),
                'status' => 'pending', // Merchant needs admin approval before becoming active
            ]);

            DB::commit();

            return $this->apiResponse->success(
                'Onboarding completed successfully! Welcome to MR Shife.',
                [
                    'merchant' => new MerchantResource($merchant->fresh()),
                    'onboarding_completed' => true,
                    'next_step' => 'dashboard',
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Onboarding completion failed: ' . $e->getMessage());
            return $this->apiResponse->error('Failed to complete onboarding');
        }
    }

    /**
     * Get onboarding status and progress
     */
    public function getOnboardingStatus(Request $request)
    {
        try {
            $merchant = auth('sanctum')->user();

            $onboardingStatus = [
                'current_step' => $merchant->registration_step,
                'progress_percentage' => $merchant->getRegistrationProgress(),
                'is_completed' => $merchant->registration_step === 'completed',
                'completed_at' => $merchant->completed_at?->toISOString(),
                'steps' => [
                    'basic_info' => [
                        'completed' => !empty($merchant->name),
                        'title' => 'Basic Information',
                        'description' => 'Name, phone, email verification',
                    ],
                    'subscription' => [
                        'completed' => !empty($merchant->subscription_plan_id),
                        'title' => 'Subscription Plan',
                        'description' => 'Choose your subscription plan',
                    ],
                    'business_info' => [
                        'completed' => !empty($merchant->business_name),
                        'title' => 'Business Information',
                        'description' => 'Business details and registration',
                    ],
                    'business_profile' => [
                        'completed' => !empty($merchant->business_description),
                        'title' => 'Business Profile',
                        'description' => 'Description, hours, social media',
                    ],
                    'location' => [
                        'completed' => !empty($merchant->location_latitude),
                        'title' => 'Location Information',
                        'description' => 'Business location and address',
                    ],
                ],
                'next_step_url' => $this->getNextStepUrl($merchant->registration_step),
            ];

            return $this->apiResponse->success(
                'Onboarding status retrieved successfully',
                $onboardingStatus
            );

        } catch (\Exception $e) {
            Log::error('Get onboarding status failed: ' . $e->getMessage());
            return $this->apiResponse->error('Failed to get onboarding status');
        }
    }

    /**
     * Get next step URL based on current registration step
     */
    private function getNextStepUrl($currentStep)
    {
        $stepUrls = [
            'basic_info' => '/merchant/onboarding/step1',
            'subscription' => '/merchant/onboarding/step2',
            'business_info' => '/merchant/onboarding/step3',
            'business_profile' => '/merchant/onboarding/step4',
            'location' => null, // Onboarding completed
            'completed' => null, // Onboarding completed
        ];

        return $stepUrls[$currentStep] ?? '/merchant/onboarding/step1';
    }

    /**
     * Normalize phone number to international format (+966)
     * Handles: +966501234567, 966501234567, 0501234567, 501234567
     * Returns: +966501234567
     */
    private function normalizePhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle different formats and convert to +966 format
        if (strlen($phoneNumber) == 13 && substr($phoneNumber, 0, 3) == '966') {
            // 966501234567 -> +966501234567
            return '+' . $phoneNumber;
        } elseif (strlen($phoneNumber) == 10 && substr($phoneNumber, 0, 1) == '0') {
            // 0501234567 -> +966501234567
            return '+966' . substr($phoneNumber, 1);
        } elseif (strlen($phoneNumber) == 9) {
            // 501234567 -> +966501234567
            return '+966' . $phoneNumber;
        }

        // Return as is if format is not recognized
        return $phoneNumber;
    }
}
