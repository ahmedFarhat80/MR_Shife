<?php

namespace App\Helpers;

class OTPHelper
{
    /**
     * Default OTP length
     */
    const DEFAULT_OTP_LENGTH = 4;

    /**
     * Maximum OTP length
     */
    const MAX_OTP_LENGTH = 6;

    /**
     * Minimum OTP length
     */
    const MIN_OTP_LENGTH = 4;

    /**
     * Generate a random OTP code.
     *
     * @param int $length Length of the OTP (4-6 digits)
     * @param bool $includeZero Whether to include leading zeros
     * @return string
     */
    public static function generateCode(int $length = self::DEFAULT_OTP_LENGTH, bool $includeZero = true): string
    {
        // Validate length
        $length = max(self::MIN_OTP_LENGTH, min(self::MAX_OTP_LENGTH, $length));

        if ($includeZero) {
            // Generate random number and pad with zeros
            $maxNumber = (int) str_repeat('9', $length);
            $code = random_int(0, $maxNumber);
            return str_pad($code, $length, '0', STR_PAD_LEFT);
        } else {
            // Generate without leading zeros
            $minNumber = (int) ('1' . str_repeat('0', $length - 1));
            $maxNumber = (int) str_repeat('9', $length);
            return (string) random_int($minNumber, $maxNumber);
        }
    }

    /**
     * Generate OTP for customer registration.
     *
     * @return string
     */
    public static function generateCustomerRegistrationCode(): string
    {
        return self::generateCode(
            config('otp.customer.registration.length', self::DEFAULT_OTP_LENGTH),
            config('otp.customer.registration.include_zero', true)
        );
    }

    /**
     * Generate OTP for customer login.
     *
     * @return string
     */
    public static function generateCustomerLoginCode(): string
    {
        return self::generateCode(
            config('otp.customer.login.length', self::DEFAULT_OTP_LENGTH),
            config('otp.customer.login.include_zero', true)
        );
    }

    /**
     * Generate OTP for merchant registration.
     *
     * @return string
     */
    public static function generateMerchantRegistrationCode(): string
    {
        return self::generateCode(
            config('otp.merchant.registration.length', self::DEFAULT_OTP_LENGTH),
            config('otp.merchant.registration.include_zero', true)
        );
    }

    /**
     * Generate OTP for merchant login.
     *
     * @return string
     */
    public static function generateMerchantLoginCode(): string
    {
        return self::generateCode(
            config('otp.merchant.login.length', self::DEFAULT_OTP_LENGTH),
            config('otp.merchant.login.include_zero', true)
        );
    }

    /**
     * Generate OTP based on user type and purpose.
     *
     * @param string $userType 'customer' or 'merchant'
     * @param string $purpose 'registration' or 'login'
     * @return string
     */
    public static function generateByType(string $userType, string $purpose = 'registration'): string
    {
        $method = 'generate' . ucfirst($userType) . ucfirst($purpose) . 'Code';
        
        if (method_exists(self::class, $method)) {
            return self::$method();
        }

        // Fallback to default generation
        return self::generateCode();
    }

    /**
     * Validate OTP format.
     *
     * @param string $code
     * @param int|null $expectedLength
     * @return bool
     */
    public static function validateFormat(string $code, ?int $expectedLength = null): bool
    {
        // Check if code contains only digits
        if (!preg_match('/^[0-9]+$/', $code)) {
            return false;
        }

        // Check length if specified
        if ($expectedLength !== null) {
            return strlen($code) === $expectedLength;
        }

        // Check if length is within acceptable range
        $length = strlen($code);
        return $length >= self::MIN_OTP_LENGTH && $length <= self::MAX_OTP_LENGTH;
    }

    /**
     * Get OTP expiry time in minutes based on type and purpose.
     *
     * @param string $userType 'customer' or 'merchant'
     * @param string $purpose 'registration' or 'login'
     * @return int
     */
    public static function getExpiryMinutes(string $userType, string $purpose = 'registration'): int
    {
        $configKey = "otp.{$userType}.{$purpose}.expiry_minutes";
        $defaultKey = "otp.{$purpose}_expiry_minutes";
        $fallback = $purpose === 'login' ? 5 : 10; // 5 minutes for login, 10 for registration

        return config($configKey, config($defaultKey, $fallback));
    }

    /**
     * Check if OTP is expired.
     *
     * @param \DateTime $expiryTime
     * @return bool
     */
    public static function isExpired(\DateTime $expiryTime): bool
    {
        return $expiryTime < now();
    }

    /**
     * Generate a secure random OTP with additional entropy.
     *
     * @param int $length
     * @return string
     */
    public static function generateSecureCode(int $length = self::DEFAULT_OTP_LENGTH): string
    {
        // Use cryptographically secure random number generation
        $length = max(self::MIN_OTP_LENGTH, min(self::MAX_OTP_LENGTH, $length));
        
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        
        return $code;
    }

    /**
     * Format OTP for display (e.g., "1234" -> "12-34" or "123-456").
     *
     * @param string $code
     * @param string $separator
     * @return string
     */
    public static function formatForDisplay(string $code, string $separator = '-'): string
    {
        $length = strlen($code);
        
        if ($length === 4) {
            return substr($code, 0, 2) . $separator . substr($code, 2, 2);
        } elseif ($length === 6) {
            return substr($code, 0, 3) . $separator . substr($code, 3, 3);
        }
        
        return $code; // Return as-is for other lengths
    }

    /**
     * Get OTP configuration for a specific user type and purpose.
     *
     * @param string $userType
     * @param string $purpose
     * @return array
     */
    public static function getConfig(string $userType, string $purpose): array
    {
        return [
            'length' => config("otp.{$userType}.{$purpose}.length", self::DEFAULT_OTP_LENGTH),
            'include_zero' => config("otp.{$userType}.{$purpose}.include_zero", true),
            'expiry_minutes' => self::getExpiryMinutes($userType, $purpose),
            'max_attempts' => config("otp.{$userType}.{$purpose}.max_attempts", 3),
            'resend_delay_seconds' => config("otp.{$userType}.{$purpose}.resend_delay_seconds", 60),
        ];
    }
}
