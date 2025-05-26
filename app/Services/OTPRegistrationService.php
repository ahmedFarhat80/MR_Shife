<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class OTPRegistrationService
{
    /**
     * Start registration process by sending OTP with user data.
     *
     * @param array $userData
     * @param string $userType
     * @return array
     */
    public function startRegistration(array $userData, string $userType): array
    {
        try {
            // Check if phone number is already registered
            if ($this->isPhoneNumberRegistered($userData['phone_number'], $userType)) {
                return [
                    'success' => false,
                    'message' => __('auth.phone_already_registered')
                ];
            }

            // Generate OTP with registration data
            $verificationCode = VerificationCode::generateRegistrationCode(
                $userData['phone_number'],
                $userType,
                $userData
            );

            // In production, send SMS here
            // $this->sendSMS($userData['phone_number'], $verificationCode->code);

            // Log OTP for development
            if (config('otp.development.log_otp_codes', false)) {
                Log::info('Registration OTP generated', [
                    'phone' => $userData['phone_number'],
                    'code' => $verificationCode->code,
                    'type' => $userType,
                    'expires_at' => $verificationCode->expires_at
                ]);
            }

            return [
                'success' => true,
                'message' => __('otp.sent_successfully'),
                'data' => [
                    'verification_code' => config('app.debug') ? $verificationCode->code : null, // Only in debug mode
                    'expires_at' => $verificationCode->expires_at,
                    'expiry_minutes' => config('otp.expiry_minutes', 1)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to start registration', [
                'error' => $e->getMessage(),
                'phone' => $userData['phone_number'] ?? 'unknown',
                'type' => $userType
            ]);

            return [
                'success' => false,
                'message' => __('otp.failed_to_send')
            ];
        }
    }

    /**
     * Verify OTP and create user account with authentication token.
     *
     * @param string $phoneNumber
     * @param string $code
     * @param string $userType
     * @return array
     */
    public function verifyOTPAndCreateAccount(string $phoneNumber, string $code, string $userType): array
    {
        try {
            // Find verification code
            $verificationCode = VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', $userType)
                ->where('code', $code)
                ->first();

            if (!$verificationCode) {
                return [
                    'success' => false,
                    'message' => __('otp.invalid_code')
                ];
            }

            if ($verificationCode->isExpired()) {
                $verificationCode->delete();
                return [
                    'success' => false,
                    'message' => __('otp.expired_code')
                ];
            }

            // Check if phone is already registered (double check)
            if ($this->isPhoneNumberRegistered($phoneNumber, $userType)) {
                $verificationCode->delete();
                return [
                    'success' => false,
                    'message' => __('auth.phone_already_registered')
                ];
            }

            // Create user account
            $userData = $verificationCode->registration_data;
            $user = $this->createUserAccount($userData, $userType);

            // Generate authentication token
            $tokenName = $userType === 'merchant'
                ? 'merchant-token'
                : 'customer-token';
            $token = $user->createToken($tokenName)->plainTextToken;

            // Update last login
            $user->update(['last_login_at' => now()]);

            // Delete verification code
            $verificationCode->delete();

            return [
                'success' => true,
                'message' => __('registration.phone_verified'),
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'user_type' => $userType,
                    'requires_business_info' => $userType === 'merchant' && empty($user->business_name),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify OTP and create account', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'type' => $userType
            ]);

            return [
                'success' => false,
                'message' => __('otp.failed_to_verify')
            ];
        }
    }

    /**
     * Resend OTP for registration.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return array
     */
    public function resendRegistrationOTP(string $phoneNumber, string $userType): array
    {
        try {
            // Find existing verification code
            $existingCode = VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', $userType)
                ->first();

            if (!$existingCode || !$existingCode->registration_data) {
                return [
                    'success' => false,
                    'message' => __('otp.no_pending_registration')
                ];
            }

            // Check if phone is already registered
            if ($this->isPhoneNumberRegistered($phoneNumber, $userType)) {
                $existingCode->delete();
                return [
                    'success' => false,
                    'message' => __('auth.phone_already_registered')
                ];
            }

            // Generate new OTP with same registration data
            $verificationCode = VerificationCode::generateRegistrationCode(
                $phoneNumber,
                $userType,
                $existingCode->registration_data
            );

            // In production, send SMS here
            // $this->sendSMS($phoneNumber, $verificationCode->code);

            return [
                'success' => true,
                'message' => __('otp.resent_successfully'),
                'data' => [
                    'verification_code' => config('app.debug') ? $verificationCode->code : null,
                    'expires_at' => $verificationCode->expires_at,
                    'expiry_minutes' => config('otp.expiry_minutes', 1)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to resend registration OTP', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'type' => $userType
            ]);

            return [
                'success' => false,
                'message' => __('otp.failed_to_resend')
            ];
        }
    }

    /**
     * Check if phone number is already registered.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return bool
     */
    private function isPhoneNumberRegistered(string $phoneNumber, string $userType): bool
    {
        if ($userType === 'merchant') {
            return Merchant::where('phone_number', $phoneNumber)->exists();
        } else {
            return Customer::where('phone_number', $phoneNumber)->exists();
        }
    }

    /**
     * Create user account from registration data.
     *
     * @param array $userData
     * @param string $userType
     * @return Customer|Merchant
     */
    private function createUserAccount(array $userData, string $userType)
    {
        // Prepare translatable fields
        $nameTranslations = [];
        if (isset($userData['name_en'])) {
            $nameTranslations['en'] = $userData['name_en'];
        }
        if (isset($userData['name_ar'])) {
            $nameTranslations['ar'] = $userData['name_ar'];
        }

        $baseData = [
            'name' => $nameTranslations,
            'phone_number' => $userData['phone_number'],
            'email' => $userData['email'] ?? null,
            'language' => App::getLocale(), // Save current language
            'phone_verified_at' => now(),
            'is_verified' => true,
            'status' => 'active',
        ];

        if ($userType === 'merchant') {
            $merchantData = array_merge($baseData, [
                'business_name' => null, // Will be filled later
                'business_address' => null,
                'business_type' => null,
                'location_latitude' => null,
                'location_longitude' => null,
                'subscription_plan' => 'free', // Default plan
                'subscription_period' => null,
            ]);

            return Merchant::create($merchantData);
        } else {
            $customerData = array_merge($baseData, [
                'address' => null,
                'location_latitude' => null,
                'location_longitude' => null,
            ]);

            return Customer::create($customerData);
        }
    }
}
