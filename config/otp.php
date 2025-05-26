<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the rate limiting behavior for OTP-related
    | endpoints. You can enable or disable rate limiting and configure
    | different limits for different types of OTP operations.
    |
    */

    'rate_limiting_enabled' => env('OTP_RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | OTP Rate Limits
    |--------------------------------------------------------------------------
    |
    | Define rate limits for different OTP operations. The format is
    | 'max_attempts,decay_minutes' where max_attempts is the maximum number
    | of requests allowed and decay_minutes is the time window in minutes.
    |
    */

    'rate_limits' => [
        'send_otp' => env('OTP_SEND_RATE_LIMIT', '5,1'), // 5 requests per minute
        'verify_otp' => env('OTP_VERIFY_RATE_LIMIT', '10,1'), // 10 requests per minute
        'resend_otp' => env('OTP_RESEND_RATE_LIMIT', '3,1'), // 3 requests per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Code Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OTP code generation and validation.
    |
    */

    'code_length' => 6, // Length of OTP codes
    'expiry_minutes' => 1, // OTP expiry time in minutes (registration OTP)
    'login_expiry_minutes' => 1, // OTP expiry time for login

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings that are useful during development and testing.
    |
    */

    'development' => [
        'bypass_rate_limiting' => env('APP_ENV') === 'local' && env('OTP_RATE_LIMITING_ENABLED', true) === false,
        'log_otp_codes' => env('APP_DEBUG', false), // Log OTP codes for debugging
    ],

];
