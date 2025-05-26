<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\VerificationCode;
use App\Services\ApiResponseService;
use App\Services\OTPService;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

class CustomerAuthController extends Controller
{
    protected ApiResponseService $apiResponse;
    protected OTPService $otpService;

    public function __construct(ApiResponseService $apiResponse, OTPService $otpService)
    {
        $this->apiResponse = $apiResponse;
        $this->otpService = $otpService;
    }

    /**
     * Register new customer
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone_number' => 'required|string|unique:customers,phone_number',
            'email' => 'nullable|email|unique:customers,email',
            'preferred_language' => 'nullable|string|in:ar,en',
        ], [
            'name_en.required' => __('validation.required', ['attribute' => __('attributes.name_en')]),
            'phone_number.required' => __('validation.required', ['attribute' => __('attributes.phone_number')]),
            'phone_number.unique' => __('validation.unique', ['attribute' => __('attributes.phone_number')]),
            'email.email' => __('validation.email', ['attribute' => __('attributes.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('attributes.email')]),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            // Check if phone number is already registered BEFORE creating customer
            $existingCustomer = Customer::where('phone_number', $request->phone_number)->first();
            if ($existingCustomer) {
                return $this->apiResponse->error(__('validation.unique', ['attribute' => __('attributes.phone_number')]));
            }

            DB::beginTransaction();

            // Create customer
            $customer = Customer::create([
                'name' => [
                    'en' => $request->name_en,
                    'ar' => $request->name_ar ?? $request->name_en,
                ],
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'preferred_language' => $request->preferred_language ?? 'ar',
                'status' => 'active',
            ]);

            // Send OTP for phone verification (skip registration check since we already checked)
            $otpResult = $this->otpService->sendOTP($request->phone_number, 'customer', 'registration', true);

            if (!$otpResult['success']) {
                DB::rollBack();
                return $this->apiResponse->error(__('registration.failed_to_send_otp') . ': ' . $otpResult['message']);
            }

            DB::commit();

            return $this->apiResponse->success(
                __('registration.customer_registered_successfully'),
                [
                    'customer' => new CustomerResource($customer),
                    'verification_code' => $otpResult['data']['verification_code'], // Remove in production
                    'expires_at' => $otpResult['data']['expires_at'],
                    'next_step' => 'phone_verification',
                ],
                [],
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('registration.failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Send phone verification OTP
     */
    public function sendPhoneVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:customers,phone_number',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $customer = Customer::where('phone_number', $request->phone_number)->first();

        if ($customer->phone_verified) {
            return $this->apiResponse->error(__('otp.already_verified'));
        }

        $result = $this->otpService->sendOTP($request->phone_number, 'customer');

        if ($result['success']) {
            return $this->apiResponse->success(
                __('otp.sent_successfully'),
                [
                    'verification_code' => $result['data']['verification_code'], // Remove in production
                    'expires_at' => $result['data']['expires_at'],
                ]
            );
        }

        return $this->apiResponse->error($result['message']);
    }

    /**
     * Verify phone number
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:customers,phone_number',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $customer = Customer::where('phone_number', $request->phone_number)->first();

            // Verify OTP
            $verificationCode = VerificationCode::where('phone_number', $request->phone_number)
                ->where('type', 'customer')
                ->where('code', $request->code)
                ->first();

            if (!$verificationCode) {
                return $this->apiResponse->error(__('otp.invalid_code'));
            }

            if ($verificationCode->isExpired()) {
                return $this->apiResponse->error(__('otp.expired_code'));
            }

            // Update customer
            $customer->update([
                'phone_verified' => true,
                'phone_verified_at' => now(),
            ]);

            // Generate authentication token
            $token = $customer->createToken('customer-auth-token')->plainTextToken;

            // Delete verification code
            $verificationCode->delete();

            DB::commit();

            return $this->apiResponse->success(
                __('customer.phone_verified_successfully'),
                [
                    'customer' => new CustomerResource($customer),
                    'token' => $token,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('otp.failed_to_verify') . ': ' . $e->getMessage());
        }
    }



    /**
     * Login with phone number only (OTP-based)
     */
    public function loginWithOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:customers,phone_number',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $customer = Customer::where('phone_number', $request->phone_number)->first();

        if ($customer->status !== 'active') {
            return $this->apiResponse->error(__('auth.account_disabled'), [], 403);
        }

        // Send OTP
        $result = $this->otpService->sendOTP($request->phone_number, 'customer', 'login');

        if ($result['success']) {
            return $this->apiResponse->success(
                __('otp.sent_successfully'),
                [
                    'verification_code' => $result['data']['verification_code'], // Remove in production
                    'expires_at' => $result['data']['expires_at'],
                    'message' => __('auth.otp_sent_for_login'),
                ]
            );
        }

        return $this->apiResponse->error($result['message']);
    }

    /**
     * Verify OTP for login
     */
    public function verifyLoginOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:customers,phone_number',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $customer = Customer::where('phone_number', $request->phone_number)->first();

            // Verify OTP
            $verificationCode = VerificationCode::where('phone_number', $request->phone_number)
                ->where('type', 'customer')
                ->where('code', $request->code)
                ->first();

            if (!$verificationCode) {
                return $this->apiResponse->error(__('otp.invalid_code'));
            }

            if ($verificationCode->isExpired()) {
                return $this->apiResponse->error(__('otp.expired_code'));
            }

            // Update last login
            $customer->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Generate authentication token
            $token = $customer->createToken('customer-auth-token')->plainTextToken;

            // Delete verification code
            $verificationCode->delete();

            DB::commit();

            return $this->apiResponse->success(
                __('auth.login_successful'),
                [
                    'customer' => new CustomerResource($customer),
                    'token' => $token,
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('auth.login_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->apiResponse->success(__('auth.logout_successful'));
    }

    /**
     * Get current customer profile
     */
    public function profile(Request $request)
    {
        return $this->apiResponse->success(
            __('api.profile_retrieved'),
            [
                'customer' => new CustomerResource($request->user()),
            ]
        );
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $request->user();

        $validator = Validator::make($request->all(), [
            'name_en' => 'nullable|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'dietary_preferences' => 'nullable|array',
            'favorite_cuisines' => 'nullable|array',
            'notifications_enabled' => 'nullable|boolean',
            'sms_notifications' => 'nullable|boolean',
            'email_notifications' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updateData = [];

            // Handle name updates
            if ($request->has('name_en') || $request->has('name_ar')) {
                $updateData['name'] = [
                    'en' => $request->name_en ?? $customer->getTranslation('name', 'en'),
                    'ar' => $request->name_ar ?? $customer->getTranslation('name', 'ar'),
                ];
            }

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $uploadResult = ImageHelper::uploadWithResize(
                    $request->file('avatar'),
                    'user_avatars',
                    'medium',
                    'public',
                    $customer->avatar
                );

                if ($uploadResult['success']) {
                    $updateData['avatar'] = $uploadResult['main_path'];
                } else {
                    throw new \Exception("Failed to upload avatar: " . $uploadResult['message']);
                }
            }

            // Handle other fields
            $fields = ['email', 'date_of_birth', 'gender', 'dietary_preferences', 'favorite_cuisines',
                      'notifications_enabled', 'sms_notifications', 'email_notifications'];

            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->$field;
                }
            }

            // Reset email verification if email changed
            if (isset($updateData['email']) && $updateData['email'] !== $customer->email) {
                $updateData['email_verified'] = false;
                $updateData['email_verified_at'] = null;
            }

            $customer->update($updateData);

            DB::commit();

            return $this->apiResponse->success(
                __('customer.profile_updated_successfully'),
                [
                    'customer' => new CustomerResource($customer->fresh()),
                ]
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error(__('customer.profile_update_failed') . ': ' . $e->getMessage());
        }
    }



    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'confirmation' => 'required|string|in:DELETE',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $customer = $request->user();

        // Delete avatar if exists
        if ($customer->avatar) {
            ImageHelper::delete($customer->avatar);
        }

        // Soft delete the customer
        $customer->delete();

        return $this->apiResponse->success(__('customer.account_deleted_successfully'));
    }
}
