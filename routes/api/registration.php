<?php

use App\Http\Controllers\Api\MerchantRegistrationController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Registration API Routes
|--------------------------------------------------------------------------
|
| Here are the registration routes for both customers and merchants.
| These routes handle user registration, phone verification, and multi-step
| registration processes.
|
*/

// New Merchant Registration System (6 Steps)
Route::prefix('registration')->name('api.registration.')->middleware(['set.language'])->group(function () {

    // Merchant Registration Routes - MOVED TO /api/merchant/registration/*
    // This section is kept for backward compatibility but routes are now in merchant.php

    // Customer Registration Routes - Redirected to /api/customer/auth/*
    // Customer registration is handled by CustomerAuthController in customer.php
    Route::prefix('customer')->name('customer.')->group(function () {
        // Redirect to proper customer registration endpoint
        Route::post('/init', function () {
            return response()->json([
                'message' => 'Customer registration has been moved',
                'redirect_to' => '/api/customer/auth/register',
                'note' => 'Please use the customer authentication endpoints'
            ], 301);
        })->name('init');

        Route::post('/verify-otp', function () {
            return response()->json([
                'message' => 'Customer OTP verification has been moved',
                'redirect_to' => '/api/customer/auth/verify-phone',
                'note' => 'Please use the customer authentication endpoints'
            ], 301);
        })->name('verify-otp');

        Route::post('/resend-otp', function () {
            return response()->json([
                'message' => 'Customer OTP resend has been moved',
                'redirect_to' => '/api/customer/auth/send-phone-verification',
                'note' => 'Please use the customer authentication endpoints'
            ], 301);
        })->name('resend-otp');
    });
});

// Common verification routes
Route::prefix('verification')->name('api.verification.')->group(function () {
    Route::post('/check-phone', function () {
        // Check if phone number is available
        return response()->json(['message' => 'Phone availability check not implemented yet']);
    })->name('check-phone');

    Route::post('/check-email', function () {
        // Check if email is available
        return response()->json(['message' => 'Email availability check not implemented yet']);
    })->name('check-email');
});

// Subscription Management Routes
Route::prefix('subscriptions')->name('api.subscriptions.')->middleware(['set.language'])->group(function () {
    // Public routes
    Route::get('/plans', [SubscriptionController::class, 'getPlans'])->name('plans');
    Route::get('/plans/{planId}', [SubscriptionController::class, 'getPlan'])->name('plan');
    Route::get('/mock-payment-form', [SubscriptionController::class, 'getMockPaymentForm'])->name('mock-payment-form');

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::get('/current', [SubscriptionController::class, 'getCurrentSubscription'])->name('current');
        Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('cancel');
    });
});
