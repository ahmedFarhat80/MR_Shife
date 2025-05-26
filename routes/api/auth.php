<?php

use App\Http\Controllers\Api\PasswordlessLoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
|
| Here are the authentication routes for passwordless login system.
| Users authenticate using phone number + OTP only.
| User type is determined automatically from the URL path.
|
*/

// Passwordless Authentication Routes (User Type from URL)
Route::prefix('auth')->name('api.auth.')->group(function () {

    // Merchant Authentication Routes
    Route::prefix('merchant')->name('merchant.')->group(function () {
        // Login routes (no authentication required)
        Route::post('/send-login-otp', [PasswordlessLoginController::class, 'sendMerchantLoginOTP'])
            ->name('send-login-otp');
        Route::post('/verify-login-otp', [PasswordlessLoginController::class, 'verifyMerchantLoginOTP'])
            ->name('verify-login-otp');
    });

    // Customer Authentication Routes
    Route::prefix('customer')->name('customer.')->group(function () {
        // Login routes (no authentication required)
        Route::post('/send-login-otp', [PasswordlessLoginController::class, 'sendCustomerLoginOTP'])
            ->name('send-login-otp');
        Route::post('/verify-login-otp', [PasswordlessLoginController::class, 'verifyCustomerLoginOTP'])
            ->name('verify-login-otp');
    });

    // Protected routes (authentication required) - Common for both user types
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', [PasswordlessLoginController::class, 'me'])->name('me');
        Route::post('/logout', [PasswordlessLoginController::class, 'logout'])->name('logout');
    });
});
