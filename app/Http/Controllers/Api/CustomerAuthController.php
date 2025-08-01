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
use Illuminate\Validation\Rule;

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
     * Register new customer (matches new_signup_screen.dart)
     */
    public function register(Request $request)
    {
        // Get country code (default to +966 for Saudi Arabia)
        $countryCode = $request->country_code ?? '+966';
        $phoneNumber = $request->phone_number;

        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{9}$/', // Saudi mobile: 9 digits only
                Rule::unique('customers')->where(function ($query) use ($phoneNumber, $countryCode) {
                    return $query->where('phone_number', $phoneNumber)
                                 ->where('country_code', $countryCode);
                }),
            ],
            'country_code' => 'nullable|string|regex:/^\+[0-9]{1,4}$/', // Optional country code like +966
            'email' => 'nullable|email|unique:customers,email',
            'agree_to_terms' => 'required|boolean|accepted',
        ], [
            'name_en.required' => 'English name is required',
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

            // Create customer
            $customer = Customer::create([
                'name' => [
                    'en' => $request->name_en,
                    'ar' => $request->name_ar ?? $request->name_en, // Use English name if Arabic not provided
                ],
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'email' => $request->email,
                'preferred_language' => 'en', // Default to English as per app
                'status' => 'active',
            ]);

            // Send 4-digit OTP (matching the app)
            $otpCode = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            VerificationCode::create([
                'phone_number' => $phoneNumber, // Store only the phone number without country code
                'code' => $otpCode,
                'type' => 'customer',
                'expires_at' => now()->addMinutes(1), // 1 minute expiry
            ]);

            DB::commit();

            return $this->apiResponse->success(
                'Registration successful. OTP sent to your phone.',
                [
                    'customer' => new CustomerResource($customer),
                    'verification_code' => $otpCode, // Remove in production
                    'expires_at' => now()->addMinutes(1)->toISOString(),
                    'next_step' => 'otp_verification',
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
     * Verify OTP - matches otp_verification_screen.dart (4 digits)
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

            // Find verification code (stored with phone number only)
            $verification = VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', 'customer')
                ->where('code', $request->otp)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verification) {
                return $this->apiResponse->error('Invalid or expired OTP');
            }

            DB::beginTransaction();

            // Delete verification code
            $verification->delete();

            // Update customer status and create token (search by phone_number and country_code)
            $customer = Customer::where('phone_number', $phoneNumber)
                ->where('country_code', $countryCode)
                ->first();
            $customer->update([
                'phone_verified' => true,
                'phone_verified_at' => now(),
            ]);

            $token = $customer->createToken('customer-token', ['customer'])->plainTextToken;

            DB::commit();

            return $this->apiResponse->success(
                'Phone verified successfully',
                [
                    'customer' => new CustomerResource($customer),
                    'token' => $token,
                    'next_step' => 'home', // Go to main app
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->apiResponse->error('Verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        try {
            $phoneNumber = $request->phone_number;

            // Generate new 4-digit OTP
            $otpCode = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            // Delete old verification codes
            VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', 'customer')
                ->delete();

            // Create new verification code
            VerificationCode::create([
                'phone_number' => $phoneNumber,
                'code' => $otpCode,
                'type' => 'customer',
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
            'preferred_language' => 'nullable|string|in:ar,en',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'addresses' => 'nullable|array',
            'addresses.*.address_line' => 'required_with:addresses|string|max:255',
            'addresses.*.city' => 'required_with:addresses|string|max:100',
            'addresses.*.area' => 'nullable|string|max:100',
            'addresses.*.building' => 'nullable|string|max:100',
            'addresses.*.floor' => 'nullable|string|max:50',
            'addresses.*.apartment' => 'nullable|string|max:50',
            'addresses.*.latitude' => 'nullable|numeric|between:-90,90',
            'addresses.*.longitude' => 'nullable|numeric|between:-180,180',
            'addresses.*.notes' => 'nullable|string|max:500',
            'addresses.*.is_default' => 'nullable|boolean',
            'default_address' => 'nullable|array',
            'default_address.address_line' => 'required_with:default_address|string|max:255',
            'default_address.city' => 'required_with:default_address|string|max:100',
            'default_address.area' => 'nullable|string|max:100',
            'default_address.building' => 'nullable|string|max:100',
            'default_address.floor' => 'nullable|string|max:50',
            'default_address.apartment' => 'nullable|string|max:50',
            'default_address.latitude' => 'nullable|numeric|between:-90,90',
            'default_address.longitude' => 'nullable|numeric|between:-180,180',
            'default_address.notes' => 'nullable|string|max:500',
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
            $fields = ['email', 'preferred_language', 'date_of_birth', 'gender', 'addresses',
                      'default_address', 'notifications_enabled', 'sms_notifications',
                      'email_notifications'];

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

    /**
     * Normalize phone number to international format (+966)
     * Handles: +966501234567, 966501234567, 0501234567, 501234567
     * Returns: +966501234567
     */
    private function normalizePhoneNumberToInternational($phoneNumber)
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
