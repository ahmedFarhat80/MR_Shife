<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\RegistrationSession;
use Illuminate\Support\Str;

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
                        'message' => 'Phone number is already registered.'
                    ];
                }
            }

            // Generate and send OTP
            $verificationCode = VerificationCode::generateCode($phoneNumber, $userType);

            // In production, send SMS here
            // $this->sendSMS($phoneNumber, $verificationCode->code);

            return [
                'success' => true,
                'message' => 'OTP sent successfully.',
                'data' => [
                    'verification_code' => $verificationCode->code, // Remove in production
                    'expires_at' => $verificationCode->expires_at
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
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
            $user = $this->findUserByPhone($phoneNumber, $userType);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Phone number is not registered.'
                ];
            }

            // Generate and send OTP for login
            $verificationCode = VerificationCode::generateCode($phoneNumber, $userType, null, 'login');

            // In production, send SMS here
            // $this->sendSMS($phoneNumber, $verificationCode->code);

            return [
                'success' => true,
                'message' => 'OTP sent successfully.',
                'data' => [
                    'verification_code' => $verificationCode->code, // Remove in production
                    'expires_at' => $verificationCode->expires_at
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage()
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
                    'message' => 'Invalid registration session.'
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
                    'message' => 'Invalid verification code.'
                ];
            }

            if ($verificationCode->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'Verification code has expired.'
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
                'message' => 'Registration completed successfully. You are now logged in.',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'user_type' => $registrationSession->user_type
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to verify OTP: ' . $e->getMessage()
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
                    'message' => 'Invalid verification code.'
                ];
            }

            if ($verificationCode->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'Verification code has expired.'
                ];
            }

            // Find user
            $user = $this->findUserByPhone($phoneNumber, $userType);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found.'
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
            $user->update(['last_login_at' => now()]);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'user_type' => $userType
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to verify OTP: ' . $e->getMessage()
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
     * Find user by phone number.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return Customer|Merchant|null
     */
    private function findUserByPhone(string $phoneNumber, string $userType)
    {
        if ($userType === 'merchant') {
            return Merchant::where('phone_number', $phoneNumber)->first();
        } else {
            return Customer::where('phone_number', $phoneNumber)->first();
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
}
