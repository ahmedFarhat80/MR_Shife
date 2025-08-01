<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\MerchantResource;
use App\Services\ApiResponseService;
use App\Services\OTPService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordlessLoginController extends Controller
{
    protected ApiResponseService $apiResponse;
    protected OTPService $otpService;

    public function __construct(ApiResponseService $apiResponse, OTPService $otpService)
    {
        $this->apiResponse = $apiResponse;
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for merchant login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMerchantLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        // Send OTP for merchant (using phone number only)
        $result = $this->otpService->sendLoginOTP($phoneNumber, 'merchant');

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
     * Send OTP for customer login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCustomerLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        // Send OTP for customer (using phone number only)
        $result = $this->otpService->sendLoginOTP($phoneNumber, 'customer');

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
     * Send OTP for login (Legacy method - kept for backward compatibility).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'user_type' => 'required|string|in:customer,merchant',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get user type
        $userType = $request->user_type;

        // Send OTP
        $result = $this->otpService->sendLoginOTP($request->phone_number, $userType);

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
     * Verify OTP and login for merchant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyMerchantLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
            'otp' => 'nullable|string|regex:/^[0-9]{4,6}$/',
            'code' => 'nullable|string|regex:/^[0-9]{4,6}$/',
        ]);

        // Check for OTP code (accept both 'code' and 'otp' fields)
        $otpCode = $request->code ?? $request->otp;
        if (!$otpCode) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.required', ['attribute' => 'code']));
            });
        } elseif (!preg_match('/^[0-9]{4,6}$/', $otpCode)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.regex', ['attribute' => 'code']));
            });
        }

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        // Verify OTP and login for merchant (using phone number only)
        $result = $this->otpService->verifyLoginOTP(
            $phoneNumber,
            $otpCode,
            'merchant'
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        // Return success with user data and token
        $user = $result['data']['user'];
        $resource = $result['data']['user_type'] === 'merchant'
            ? new MerchantResource($user)
            : new CustomerResource($user);

        return $this->apiResponse->success(
            $result['message'],
            [
                'user' => $resource,
                'token' => $result['data']['token'],
                'user_type' => $result['data']['user_type'],
            ]
        );
    }

    /**
     * Verify OTP and login for customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyCustomerLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
            'otp' => 'nullable|string|regex:/^[0-9]{4,6}$/',
            'code' => 'nullable|string|regex:/^[0-9]{4,6}$/',
        ]);

        // Check for OTP code (accept both 'code' and 'otp' fields)
        $otpCode = $request->code ?? $request->otp;
        if (!$otpCode) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.required', ['attribute' => 'code']));
            });
        } elseif (!preg_match('/^[0-9]{4,6}$/', $otpCode)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.regex', ['attribute' => 'code']));
            });
        }

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        // Verify OTP and login for customer (using phone number only)
        $result = $this->otpService->verifyLoginOTP(
            $phoneNumber,
            $otpCode,
            'customer'
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        // Return success with user data and token
        $user = $result['data']['user'];
        $resource = $result['data']['user_type'] === 'customer'
            ? new CustomerResource($user)
            : new MerchantResource($user);

        return $this->apiResponse->success(
            $result['message'],
            [
                'user' => $resource,
                'token' => $result['data']['token'],
                'user_type' => $result['data']['user_type'],
            ]
        );
    }

    /**
     * Verify OTP and login (Legacy method - kept for backward compatibility).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'user_type' => 'required|string|in:customer,merchant',
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/',
        ]);

        // Check for OTP code (accept both 'code' and 'otp' fields)
        $otpCode = $request->code ?? $request->otp;
        if (!$otpCode) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.required', ['attribute' => 'code']));
            });
        } elseif (!preg_match('/^[0-9]{4,6}$/', $otpCode)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('code', __('validation.regex', ['attribute' => 'code']));
            });
        }

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        // Get user type
        $userType = $request->user_type;

        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $fullPhoneNumber = $countryCode . $request->phone_number;

        // Verify OTP and login
        $result = $this->otpService->verifyLoginOTP(
            $fullPhoneNumber,
            $otpCode,
            $userType
        );

        if (!$result['success']) {
            return $this->apiResponse->error($result['message']);
        }

        // Return success with user data and token
        $user = $result['data']['user'];
        $resource = $result['data']['user_type'] === 'merchant'
            ? new MerchantResource($user)
            : new CustomerResource($user);

        return $this->apiResponse->success(
            $result['message'],
            [
                'user' => $resource,
                'token' => $result['data']['token'],
                'user_type' => $result['data']['user_type'],
            ]
        );
    }

    /**
     * Logout user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Delete current access token
            $request->user()->currentAccessToken()->delete();

            return $this->apiResponse->success(__('auth.logged_out_successfully'));
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('auth.failed_to_logout') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get current user information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            // Determine user type based on model
            $userType = $user instanceof \App\Models\Merchant ? 'merchant' : 'customer';

            $resource = $userType === 'merchant'
                ? new MerchantResource($user)
                : new CustomerResource($user);

            return $this->apiResponse->success(
                __('auth.user_information_retrieved'),
                [
                    'user' => $resource,
                    'user_type' => $userType,
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponse->error(__('auth.failed_to_retrieve_user_info') . ': ' . $e->getMessage());
        }
    }
}
