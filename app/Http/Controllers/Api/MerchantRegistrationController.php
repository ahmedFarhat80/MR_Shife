<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\SubscriptionPlan;
use App\Services\ApiResponseService;
use App\Services\MerchantRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     * Step 1: Register basic information
     */
    public function registerBasicInfo(Request $request)
    {
        // Check if this is an update request
        $isUpdate = $request->has('merchant_id') && !empty($request->merchant_id);
        
        // Build validation rules dynamically
        $rules = [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'preferred_language' => 'nullable|string|in:ar,en',
        ];

        if ($isUpdate) {
            $rules['merchant_id'] = 'required|integer|exists:merchants,id';
            $rules['phone_number'] = 'required|string|unique:merchants,phone_number,' . $request->merchant_id;
            $rules['email'] = 'nullable|email|unique:merchants,email,' . $request->merchant_id;
        } else {
            $rules['phone_number'] = 'required|string|unique:merchants,phone_number';
            $rules['email'] = 'nullable|email|unique:merchants,email';
        }

        $validator = Validator::make($request->all(), $rules, [
            'name_en.required' => __('validation.required', ['attribute' => __('attributes.name_en')]),
            'phone_number.required' => __('validation.required', ['attribute' => __('attributes.phone_number')]),
            'phone_number.unique' => __('validation.unique', ['attribute' => __('attributes.phone_number')]),
            'email.email' => __('validation.email', ['attribute' => __('attributes.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('attributes.email')]),
            'merchant_id.required' => __('validation.required', ['attribute' => 'merchant_id']),
            'merchant_id.exists' => __('validation.exists', ['attribute' => 'merchant_id']),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $result = $this->registrationService->registerBasicInfo($request->all());

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'verification_code' => $result['data']['verification_code'] ?? null, // Include OTP in response
                'expires_at' => $result['data']['expires_at'] ?? null,
                'next_step' => $result['data']['next_step'],
                'is_update' => $result['data']['is_update'] ?? false,
            ],
            [],
            $isUpdate ? 200 : 201
        );
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
     * Step 3: Get subscription plans
     */
    public function getSubscriptionPlans()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->apiResponse->success(
            __('subscription.plans_retrieved'),
            $plans
        );
    }

    /**
     * Step 3: Choose subscription plan
     */
    public function chooseSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_plan_id' => 'required|integer|exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get merchant from authenticated user token
        $merchant = $request->user();
        if (!$merchant) {
            return $this->apiResponse->error(__('auth.unauthenticated'), [], 401);
        }

        $result = $this->registrationService->chooseSubscription(
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
                'subscription_plan' => $result['data']['subscription_plan'],
                'requires_payment' => $result['data']['requires_payment'],
                'next_step' => $result['data']['next_step'],
            ]
        );
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
            'commercial_registration_number' => 'nullable|string|max:100',
            'work_permit' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_or_passport' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'health_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

        return $this->apiResponse->success(
            $result['message'],
            [
                'merchant' => new MerchantResource($result['data']['merchant']),
                'next_step' => $result['data']['next_step'],
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
        
        // Handle social_media JSON string
        if (isset($data['social_media']) && is_string($data['social_media'])) {
            $socialMedia = json_decode($data['social_media'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['social_media'] = $socialMedia;
            }
        }

        $validator = Validator::make($data, [
            'business_logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'business_description_en' => 'nullable|string|max:1000',
            'business_description_ar' => 'nullable|string|max:1000',
            'business_hours' => 'nullable|array',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email',
            'social_media' => 'nullable|array',
            'social_media.facebook' => 'nullable|url',
            'social_media.instagram' => 'nullable|url',
            'social_media.twitter' => 'nullable|url',
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
} 