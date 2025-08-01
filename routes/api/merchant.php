<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MerchantRegistrationController;
use App\Http\Controllers\Api\MerchantProfileController;
use App\Http\Controllers\Api\PasswordlessLoginController;

/*
|--------------------------------------------------------------------------
| Merchant API Routes
|--------------------------------------------------------------------------
|
| كل ما يخص التجار - التسجيل، تسجيل الدخول، Onboarding، الملف الشخصي، والإدارة
| All merchant-related routes - registration, login, onboarding, profile, management
|
*/

// ========================================
// 🔐 MERCHANT AUTHENTICATION (Public)
// ========================================
Route::prefix('merchant')->name('merchant.')->group(function () {

    // Registration & OTP
    Route::post('/register', [MerchantRegistrationController::class, 'register'])->name('register');
    Route::post('/verify-otp', [MerchantRegistrationController::class, 'verifyOTP'])->name('verify-otp');
    Route::post('/resend-otp', [MerchantRegistrationController::class, 'resendOTP'])->name('resend-otp');

    // Login & OTP
    Route::post('/send-login-otp', [PasswordlessLoginController::class, 'sendMerchantLoginOTP'])->name('send-login-otp');
    Route::post('/verify-login-otp', [PasswordlessLoginController::class, 'verifyMerchantLoginOTP'])->name('verify-login-otp');
});

// ========================================
// 🔒 MERCHANT PROTECTED ROUTES
// ========================================
Route::prefix('merchant')->middleware(['auth:sanctum'])->name('merchant.')->group(function () {

    // ========================================
    // 🏪 ONBOARDING (Protected) - 4 Steps as per Mobile App
    // ========================================
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/plans', [MerchantRegistrationController::class, 'getSubscriptionPlans'])->name('plans');
        Route::post('/step1', [MerchantRegistrationController::class, 'chooseSubscription'])->name('step1'); // Choose subscription plan
        Route::post('/step2', [MerchantRegistrationController::class, 'updateBusinessInfo'])->name('step2'); // Business info + documents (3 PDF files)
        Route::post('/step3', [MerchantRegistrationController::class, 'updateBusinessProfile'])->name('step3'); // Additional info (placeholder)
        Route::post('/step4', [MerchantRegistrationController::class, 'completeOnboarding'])->name('step4'); // Complete onboarding
        Route::get('/status', [MerchantRegistrationController::class, 'getOnboardingStatus'])->name('status');
    });

    // ========================================
    // 👤 PROFILE MANAGEMENT (Requires completed onboarding)
    // ========================================
    Route::prefix('profile')->middleware('merchant.onboarding')->name('profile.')->group(function () {
        Route::get('/', [MerchantProfileController::class, 'profile'])->name('show');
        Route::put('/', [MerchantProfileController::class, 'updateProfile'])->name('update');
        Route::post('/', [MerchantProfileController::class, 'updateProfile'])->name('update-post'); // Support for form-data uploads

        // Business settings
        Route::put('/business-info', [MerchantProfileController::class, 'updateBusinessInfo'])->name('update-business-info');
        Route::put('/notification-settings', [MerchantProfileController::class, 'updateNotificationSettings'])->name('notification-settings');

        // Dashboard and analytics
        Route::get('/dashboard', [MerchantProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [MerchantProfileController::class, 'statistics'])->name('statistics');
    });

    // ========================================
    // 🔧 SHARED ROUTES (User info, logout)
    // ========================================
    Route::get('/me', [MerchantProfileController::class, 'profile'])->name('me');
    Route::post('/logout', [PasswordlessLoginController::class, 'logout'])->name('logout');
    Route::delete('/delete-account', [MerchantProfileController::class, 'deleteAccount'])->name('delete-account');
});
