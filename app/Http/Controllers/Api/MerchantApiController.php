<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Models\Merchant;
use App\Models\VerificationCode;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MerchantApiController extends Controller
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Register a new merchant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone_number' => 'required|string|unique:merchants,phone_number',
            'email' => 'nullable|email|unique:merchants,email',
            'password' => 'required|string|min:8|confirmed',
            'business_name_en' => 'required|string|max:255',
            'business_name_ar' => 'nullable|string|max:255',
            'business_address_en' => 'nullable|string',
            'business_address_ar' => 'nullable|string',
            'business_type' => 'required|string|max:100',
            'subscription_plan' => 'nullable|string|in:free,premium',
            'subscription_period' => 'nullable|string|in:monthly,half_year,annual',
            'preferred_language' => 'nullable|string|in:en,ar',
        ], [
            'name_en.required' => __('validation.required', ['attribute' => __('attributes.name_en')]),
            'name_en.string' => __('validation.string', ['attribute' => __('attributes.name_en')]),
            'name_en.max' => __('validation.max', ['attribute' => __('attributes.name_en'), 'max' => 255]),
            'name_ar.string' => __('validation.string', ['attribute' => __('attributes.name_ar')]),
            'name_ar.max' => __('validation.max', ['attribute' => __('attributes.name_ar'), 'max' => 255]),
            'phone_number.required' => __('validation.required', ['attribute' => __('attributes.phone_number')]),
            'phone_number.string' => __('validation.string', ['attribute' => __('attributes.phone_number')]),
            'phone_number.unique' => __('validation.unique', ['attribute' => __('attributes.phone_number')]),
            'email.email' => __('validation.email', ['attribute' => __('attributes.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('attributes.email')]),
            'password.required' => __('validation.required', ['attribute' => __('attributes.password')]),
            'password.string' => __('validation.string', ['attribute' => __('attributes.password')]),
            'password.min' => __('validation.min', ['attribute' => __('attributes.password'), 'min' => 8]),
            'business_name_en.required' => __('validation.required', ['attribute' => __('attributes.business_name_en')]),
            'business_name_en.string' => __('validation.string', ['attribute' => __('attributes.business_name_en')]),
            'business_name_en.max' => __('validation.max', ['attribute' => __('attributes.business_name_en'), 'max' => 255]),
            'business_name_ar.string' => __('validation.string', ['attribute' => __('attributes.business_name_ar')]),
            'business_name_ar.max' => __('validation.max', ['attribute' => __('attributes.business_name_ar'), 'max' => 255]),
            'business_address_en.string' => __('validation.string', ['attribute' => __('attributes.business_address_en')]),
            'business_address_ar.string' => __('validation.string', ['attribute' => __('attributes.business_address_ar')]),
            'business_type.required' => __('validation.required', ['attribute' => __('attributes.business_type')]),
            'business_type.string' => __('validation.string', ['attribute' => __('attributes.business_type')]),
            'subscription_plan.string' => __('validation.string', ['attribute' => __('attributes.subscription_plan')]),
            'subscription_plan.in' => __('validation.in', ['attribute' => __('attributes.subscription_plan')]),
            'subscription_period.string' => __('validation.string', ['attribute' => __('attributes.subscription_period')]),
            'subscription_period.in' => __('validation.in', ['attribute' => __('attributes.subscription_period')]),
            'preferred_language.string' => __('validation.string', ['attribute' => __('attributes.preferred_language')]),
            'preferred_language.in' => __('validation.in', ['attribute' => __('attributes.preferred_language')]),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Create DTO from request
        $dto = \App\DTO\RegistrationDTO::fromRequest($request->all(), 'merchant');

        // Use registration service to register the merchant
        $registrationService = new \App\Services\RegistrationService();
        $result = $registrationService->register($dto);

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        // In a real application, you would send this code via SMS
        // For now, we'll just return it in the response for testing
        return $this->apiResponse->success(
            $result['message'],
            [
                'verification_code' => $result['data']['verification_code'], // Remove this in production
                'user' => new MerchantResource($result['data']['user']),
            ],
            [],
            201
        );
    }

    /**
     * Verify phone number with code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:merchants,phone_number',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ], [
            'phone_number.required' => __('validation.required', ['attribute' => __('attributes.phone_number')]),
            'phone_number.string' => __('validation.string', ['attribute' => __('attributes.phone_number')]),
            'code.required' => __('validation.required', ['attribute' => __('attributes.code')]),
            'code.string' => __('validation.string', ['attribute' => __('attributes.code')]),
            'code.size' => __('validation.size', ['attribute' => __('attributes.code'), 'size' => 6]),
            'code.regex' => __('validation.regex', ['attribute' => __('attributes.code')]),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $verificationCode = VerificationCode::where('phone_number', $request->phone_number)
            ->where('type', 'merchant')
            ->where('code', $request->code)
            ->first();

        if (!$verificationCode) {
            return $this->apiResponse->error(__('otp.invalid_code'));
        }

        if ($verificationCode->isExpired()) {
            return $this->apiResponse->error(__('otp.expired_code'));
        }

        // Update merchant
        $merchant = Merchant::where('phone_number', $request->phone_number)->first();
        $merchant->phone_verified_at = now();
        $merchant->is_verified = true;
        $merchant->status = 'active';
        $merchant->save();

        // Delete the verification code
        $verificationCode->delete();

        // Create token
        $token = $merchant->createToken('merchant-token')->plainTextToken;

        return $this->apiResponse->success(
            __('merchant.phone_verified_successfully'),
            [
                'user' => new MerchantResource($merchant),
                'token' => $token,
            ]
        );
    }

    /**
     * Login a merchant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ], [
            'phone_number.required' => __('validation.required', ['attribute' => __('attributes.phone_number')]),
            'phone_number.string' => __('validation.string', ['attribute' => __('attributes.phone_number')]),
            'password.required' => __('validation.required', ['attribute' => __('attributes.password')]),
            'password.string' => __('validation.string', ['attribute' => __('attributes.password')]),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $merchant = Merchant::where('phone_number', $request->phone_number)->first();

        if (!$merchant || !Hash::check($request->password, $merchant->password)) {
            return $this->apiResponse->error(__('auth.invalid_credentials'), null, 401);
        }

        if ($merchant->status !== 'active') {
            return $this->apiResponse->error(__('auth.unauthenticated'), null, 403);
        }

        // Revoke all existing tokens
        $merchant->tokens()->delete();

        // Create new token
        $token = $merchant->createToken('merchant-token')->plainTextToken;

        return $this->apiResponse->success(
            __('auth.login_successful'),
            [
                'user' => new MerchantResource($merchant),
                'token' => $token,
            ]
        );
    }

    /**
     * Logout a merchant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->apiResponse->success(__('auth.logout_successful'));
    }

    /**
     * Get the authenticated merchant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return $this->apiResponse->success(
            __('auth.user_information_retrieved'),
            new MerchantResource($request->user())
        );
    }
}
