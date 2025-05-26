<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MerchantRegistrationController;
use App\Http\Controllers\Api\MerchantProfileController;
use App\Http\Controllers\Api\MerchantApiController;
use App\Http\Controllers\Api\Vendor\ProductController;
use App\Http\Controllers\Api\Vendor\CategoryController;
use App\Http\Controllers\Api\Vendor\OrderController;
use App\Http\Controllers\Api\Vendor\AnalyticsController;

/*
|--------------------------------------------------------------------------
| Merchant API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for merchant registration, authentication,
| profile management, and merchant-specific functionality.
|
*/

// Public merchant routes (no authentication required)
Route::prefix('merchant')->group(function () {
    Route::prefix('registration')->name('api.merchant.registration.')->group(function () {
        // Registration steps
        Route::post('/basic-info', [MerchantRegistrationController::class, 'registerBasicInfo'])->name('basic-info');
        Route::post('/send-phone-verification', [MerchantRegistrationController::class, 'sendPhoneVerification'])->name('send-phone-verification');
        Route::post('/verify-phone', [MerchantRegistrationController::class, 'verifyPhone'])->name('verify-phone');

        // Subscription plans (public)
        Route::get('/subscription-plans', [MerchantRegistrationController::class, 'getSubscriptionPlans'])->name('subscription-plans');
    });
});

// Protected merchant routes (authentication required)
Route::prefix('merchant')->middleware(['auth:sanctum'])->group(function () {

    // Registration completion (requires authentication)
    Route::prefix('registration')->name('api.merchant.registration.')->group(function () {
        Route::post('/subscription', [MerchantRegistrationController::class, 'chooseSubscription'])->name('subscription');
        Route::post('/payment', [MerchantRegistrationController::class, 'processPayment'])->name('payment');
        Route::post('/business-info', [MerchantRegistrationController::class, 'updateBusinessInfo'])->name('business-info');
        Route::post('/business-profile', [MerchantRegistrationController::class, 'updateBusinessProfile'])->name('business-profile');
        Route::post('/location', [MerchantRegistrationController::class, 'updateLocation'])->name('location');
        Route::get('/status', [MerchantRegistrationController::class, 'getRegistrationStatus'])->name('status');
    });

    // Merchant profile management
    Route::prefix('profile')->name('api.merchant.profile.')->group(function () {
        // Basic profile operations
        Route::get('/', [MerchantProfileController::class, 'profile'])->name('show');
        Route::put('/basic-info', [MerchantProfileController::class, 'updateBasicInfo'])->name('update-basic-info');
        Route::put('/business-info', [MerchantProfileController::class, 'updateBusinessInfo'])->name('update-business-info');
        Route::put('/business-profile', [MerchantProfileController::class, 'updateBusinessProfile'])->name('update-business-profile');
        Route::put('/location', [MerchantProfileController::class, 'updateLocation'])->name('update-location');

        // Account management
        Route::put('/notification-settings', [MerchantProfileController::class, 'updateNotificationSettings'])->name('notification-settings');

        // Analytics and dashboard
        Route::get('/dashboard', [MerchantProfileController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [MerchantProfileController::class, 'statistics'])->name('statistics');
    });

    // Merchant business operations
    Route::prefix('business')->name('api.merchant.business.')->group(function () {
        // Dashboard and analytics
        Route::get('/dashboard', [MerchantApiController::class, 'dashboard'])->name('dashboard');
        Route::get('/analytics', [MerchantApiController::class, 'getAnalytics'])->name('analytics');
        Route::get('/revenue-analytics', [MerchantApiController::class, 'getRevenueAnalytics'])->name('revenue-analytics');
        Route::get('/order-analytics', [MerchantApiController::class, 'getOrderAnalytics'])->name('order-analytics');

        // Order management
        Route::get('/orders', [MerchantApiController::class, 'getOrders'])->name('orders');
        Route::get('/orders/{id}', [MerchantApiController::class, 'getOrder'])->name('order-details');
        Route::put('/orders/{id}/status', [MerchantApiController::class, 'updateOrderStatus'])->name('update-order-status');
        Route::post('/orders/{id}/accept', [MerchantApiController::class, 'acceptOrder'])->name('accept-order');
        Route::post('/orders/{id}/reject', [MerchantApiController::class, 'rejectOrder'])->name('reject-order');

        // Product/Menu management
        Route::get('/products', [MerchantApiController::class, 'getProducts'])->name('products');
        Route::post('/products', [MerchantApiController::class, 'createProduct'])->name('create-product');
        Route::get('/products/{id}', [MerchantApiController::class, 'getProduct'])->name('product-details');
        Route::put('/products/{id}', [MerchantApiController::class, 'updateProduct'])->name('update-product');
        Route::delete('/products/{id}', [MerchantApiController::class, 'deleteProduct'])->name('delete-product');
        Route::put('/products/{id}/toggle-availability', [MerchantApiController::class, 'toggleProductAvailability'])->name('toggle-product-availability');

        // Category management
        Route::get('/categories', [MerchantApiController::class, 'getCategories'])->name('categories');
        Route::post('/categories', [MerchantApiController::class, 'createCategory'])->name('create-category');
        Route::put('/categories/{id}', [MerchantApiController::class, 'updateCategory'])->name('update-category');
        Route::delete('/categories/{id}', [MerchantApiController::class, 'deleteCategory'])->name('delete-category');

        // Customer management
        Route::get('/customers', [MerchantApiController::class, 'getCustomers'])->name('customers');
        Route::get('/customers/{id}', [MerchantApiController::class, 'getCustomer'])->name('customer-details');
        Route::get('/customer-analytics', [MerchantApiController::class, 'getCustomerAnalytics'])->name('customer-analytics');

        // Reviews and ratings
        Route::get('/reviews', [MerchantApiController::class, 'getReviews'])->name('reviews');
        Route::post('/reviews/{id}/reply', [MerchantApiController::class, 'replyToReview'])->name('reply-to-review');

        // Notifications
        Route::get('/notifications', [MerchantApiController::class, 'getNotifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [MerchantApiController::class, 'markNotificationAsRead'])->name('mark-notification-read');
        Route::post('/notifications/read-all', [MerchantApiController::class, 'markAllNotificationsAsRead'])->name('mark-all-notifications-read');

        // Business settings
        Route::get('/settings', [MerchantApiController::class, 'getBusinessSettings'])->name('settings');
        Route::put('/settings', [MerchantApiController::class, 'updateBusinessSettings'])->name('update-settings');
        Route::put('/operating-hours', [MerchantApiController::class, 'updateOperatingHours'])->name('update-operating-hours');
        Route::put('/delivery-settings', [MerchantApiController::class, 'updateDeliverySettings'])->name('update-delivery-settings');
    });
});
