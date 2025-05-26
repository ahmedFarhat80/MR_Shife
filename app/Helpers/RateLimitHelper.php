<?php

namespace App\Helpers;

class RateLimitHelper
{
    /**
     * Get the appropriate middleware for OTP rate limiting.
     * Returns rate limiting middleware if enabled, empty array if disabled.
     *
     * @param string $operation The OTP operation type (send_otp, verify_otp, resend_otp)
     * @param string|null $customRule Custom rate limit rule to override default
     * @return array
     */
    public static function getOTPRateLimitMiddleware(string $operation = 'send_otp', ?string $customRule = null): array
    {
        $isEnabled = config('otp.rate_limiting_enabled', true);

        if (!$isEnabled) {
            return []; // No rate limiting
        }

        // Use custom rule if provided, otherwise get from config
        $rateLimitRule = $customRule ?? config("otp.rate_limits.$operation", '5,1');

        return ["throttle:$rateLimitRule"];
    }

    /**
     * Check if OTP rate limiting is enabled.
     *
     * @return bool
     */
    public static function isOTPRateLimitingEnabled(): bool
    {
        return config('otp.rate_limiting_enabled', true);
    }

    /**
     * Get conditional middleware array for OTP endpoints.
     * This can be used directly in route definitions.
     *
     * @param string $rateLimitRule The rate limit rule
     * @param array $additionalMiddleware Additional middleware to always apply
     * @return array
     */
    public static function getConditionalOTPMiddleware(string $rateLimitRule = '5,1', array $additionalMiddleware = []): array
    {
        $middleware = $additionalMiddleware;

        if (self::isOTPRateLimitingEnabled()) {
            $middleware[] = "throttle:$rateLimitRule";
        }

        return $middleware;
    }
}
