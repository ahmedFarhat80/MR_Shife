<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\RegistrationSession;


class OTPService
{
    /**
     * Send OTP (generic method).
     *
     * @param string $phoneNumber
     * @param string $userType
     * @param string $purpose
     * @param bool $skipRegistrationCheck
     * @return array
     */
    public function sendOTP(string $phoneNumber, string $userType, string $purpose = 'registration', bool $skipRegistrationCheck = false): array
    {
        if ($purpose === 'login') {
            return $this->sendLoginOTP($phoneNumber, $userType);
        }

        return $this->sendRegistrationOTP($phoneNumber, $userType, $skipRegistrationCheck);
    }

    /**
     * Send OTP for registration verification.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @param bool $skipRegistrationCheck
     * @return array
     */
    public function sendRegistrationOTP(string $phoneNumber, string $userType, bool $skipRegistrationCheck = false): array
    {
        try {
            // Check if phone number is already registered (unless skipped)
            if (!$skipRegistrationCheck) {
                $isRegistered = $this->isPhoneNumberRegistered($phoneNumber, $userType);
                if ($isRegistered) {
                    return [
                        'success' => false,
                        'message' => __('auth.phone_already_registered')
                    ];
                }
            }

            // Generate and send OTP
            $verificationCode = VerificationCode::generateCode($phoneNumber, $userType);

            // In production, send SMS here
            // $this->sendSMS($phoneNumber, $verificationCode->code);

            return [
                'success' => true,
                'message' => __('otp.sent_successfully'),
                'data' => [
                    'verification_code' => $verificationCode->code, // Remove in production
                    'expires_at' => $verificationCode->expires_at
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('otp.failed_to_send') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send OTP for login.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return array
     */
    public function sendLoginOTP(string $phoneNumber, string $userType): array
    {
        try {
            // Check if phone number is registered
            $defaultCountryCode = '+966';
            if ($userType === 'merchant') {
                $user = Merchant::where('phone_number', $phoneNumber)
                    ->where('country_code', $defaultCountryCode)
                    ->first();
            } else {
                $user = Customer::where('phone_number', $phoneNumber)
                    ->where('country_code', $defaultCountryCode)
                    ->first();
            }
            if (!$user) {
                return [
                    'success' => false,
                    'message' => __('auth.phone_not_registered')
                ];
            }

            // Generate and send OTP for login (using phone number only)
            $verificationCode = VerificationCode::generateCode($phoneNumber, $userType, null, 'login');

            // In production, send SMS here
            // $this->sendSMS($normalizedPhone, $verificationCode->code);

            return [
                'success' => true,
                'message' => __('otp.sent_successfully'),
                'data' => [
                    'verification_code' => $verificationCode->code, // Remove in production
                    'expires_at' => $verificationCode->expires_at
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('otp.failed_to_send') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP and complete registration.
     *
     * @param string $sessionId
     * @param string $phoneNumber
     * @param string $code
     * @return array
     */
    public function verifyRegistrationOTP(string $sessionId, string $phoneNumber, string $code): array
    {
        try {
            // Find registration session
            $registrationSession = RegistrationSession::findBySessionId($sessionId);
            if (!$registrationSession) {
                return [
                    'success' => false,
                    'message' => __('otp.no_pending_registration')
                ];
            }

            // Verify OTP
            $verificationCode = VerificationCode::where('phone_number', $phoneNumber)
                ->where('type', $registrationSession->user_type)
                ->where('code', $code)
                ->first();

            if (!$verificationCode) {
                return [
                    'success' => false,
                    'message' => __('otp.invalid_code')
                ];
            }

            if ($verificationCode->isExpired()) {
                return [
                    'success' => false,
                    'message' => __('otp.expired_code')
                ];
            }

            // Create user account
            $userData = $registrationSession->data;
            $user = $this->createUserAccount($userData, $registrationSession->user_type);

            // Delete verification code and registration session
            $verificationCode->delete();
            $registrationSession->delete();

            // Generate authentication token
            $tokenName = $registrationSession->user_type === 'merchant'
                ? 'merchant-token'
                : 'customer-token';
            $token = $user->createToken($tokenName)->plainTextToken;

            // Update last login
            $user->update(['last_login_at' => now()]);

            return [
                'success' => true,
                'message' => __('registration.completed'),
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'user_type' => $registrationSession->user_type
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('otp.failed_to_verify') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP for login.
     *
     * @param string $phoneNumber
     * @param string $code
     * @param string $userType
     * @return array
     */
    public function verifyLoginOTP(string $phoneNumber, string $code, string $userType): array
    {
        try {
            // Verify OTP
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
                return [
                    'success' => false,
                    'message' => __('otp.expired_code')
                ];
            }

            // Find user directly
            $defaultCountryCode = '+966';
            if ($userType === 'merchant') {
                $user = Merchant::where('phone_number', $phoneNumber)
                    ->where('country_code', $defaultCountryCode)
                    ->first();
            } else {
                $user = Customer::where('phone_number', $phoneNumber)
                    ->where('country_code', $defaultCountryCode)
                    ->first();
            }

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Delete verification code
            $verificationCode->delete();

            // Generate authentication token
            $tokenName = $userType === 'merchant'
                ? 'merchant-token'
                : 'customer-token';
            $token = $user->createToken($tokenName)->plainTextToken;

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip()
            ]);

            return [
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user, // Return the actual model object
                    'token' => $token,
                    'user_type' => $userType
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('otp.failed_to_verify') . ': ' . $e->getMessage()
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
     * Parse phone number to separate country code and number.
     *
     * @param string $phoneNumber
     * @return array
     */
    private function parsePhoneNumber(string $phoneNumber): array
    {
        // Normalize the phone number first
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

        // Default values
        $countryCode = '+966';
        $number = '';

        // Remove the + sign for processing
        $cleanPhone = ltrim($normalizedPhone, '+');

        if (str_starts_with($cleanPhone, '966')) {
            // Phone number with Saudi country code
            $countryCode = '+966';
            $number = substr($cleanPhone, 3); // Remove 966
        } else {
            // Assume it's a local number without country code
            $countryCode = '+966';
            $number = $cleanPhone;
        }

        return [
            'country_code' => $countryCode,
            'number' => $number,
            'full_number' => $normalizedPhone
        ];
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
            'phone_verified_at' => now(),
            'is_verified' => true,
            'status' => 'active',
        ];

        if ($userType === 'merchant') {
            // Prepare business name translations
            $businessNameTranslations = [];
            if (isset($userData['business_name_en'])) {
                $businessNameTranslations['en'] = $userData['business_name_en'];
            }
            if (isset($userData['business_name_ar'])) {
                $businessNameTranslations['ar'] = $userData['business_name_ar'];
            }

            // Prepare business address translations
            $businessAddressTranslations = [];
            if (isset($userData['business_address_en'])) {
                $businessAddressTranslations['en'] = $userData['business_address_en'];
            }
            if (isset($userData['business_address_ar'])) {
                $businessAddressTranslations['ar'] = $userData['business_address_ar'];
            }

            $merchantData = array_merge($baseData, [
                'business_name' => $businessNameTranslations,
                'business_address' => $businessAddressTranslations ?: null,
                'business_type' => $userData['business_type'] ?? null,
                'location_latitude' => $userData['location_latitude'] ?? null,
                'location_longitude' => $userData['location_longitude'] ?? null,
                'subscription_plan' => $userData['subscription_plan'] ?? 'free',
                'subscription_period' => $userData['subscription_period'] ?? null,
            ]);

            return Merchant::create($merchantData);
        } else {
            // Prepare address translations
            $addressTranslations = [];
            if (isset($userData['address_en'])) {
                $addressTranslations['en'] = $userData['address_en'];
            }
            if (isset($userData['address_ar'])) {
                $addressTranslations['ar'] = $userData['address_ar'];
            }

            $customerData = array_merge($baseData, [
                'address' => $addressTranslations ?: null,
                'location_latitude' => $userData['location_latitude'] ?? null,
                'location_longitude' => $userData['location_longitude'] ?? null,
            ]);

            return Customer::create($customerData);
        }
    }

    /**
     * Normalize phone number to international format (+966)
     *
     * @param string $phoneNumber
     * @return string
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters except +
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // If already starts with +966, return as is
        if (str_starts_with($phoneNumber, '+966')) {
            return $phoneNumber;
        }

        // Remove + sign for processing
        $cleanPhone = ltrim($phoneNumber, '+');

        // Handle different formats and convert to +966 format
        if (strlen($cleanPhone) == 13 && str_starts_with($cleanPhone, '966')) {
            // 966501234567 -> +966501234567
            return '+' . $cleanPhone;
        } elseif (strlen($cleanPhone) == 10 && str_starts_with($cleanPhone, '0')) {
            // 0501234567 -> +966501234567
            return '+966' . substr($cleanPhone, 1);
        } elseif (strlen($cleanPhone) == 9) {
            // 501234567 -> +966501234567
            return '+966' . $cleanPhone;
        } elseif (strlen($cleanPhone) == 12 && str_starts_with($cleanPhone, '966')) {
            // 966501234567 (12 digits) -> +966501234567
            return '+' . $cleanPhone;
        }

        // If none of the above, assume it's a local number and add +966
        return '+966' . $cleanPhone;
    }
}
