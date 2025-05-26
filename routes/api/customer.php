<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerApiController;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for customer authentication, profile management,
| and customer-specific functionality.
|
*/

// Public customer routes (no authentication required)
Route::prefix('customer')->group(function () {
    Route::prefix('auth')->name('api.customer.auth.')->group(function () {
        // Registration and authentication
        Route::post('/register', [CustomerAuthController::class, 'register'])->name('register');
        Route::post('/send-phone-verification', [CustomerAuthController::class, 'sendPhoneVerification'])->name('send-phone-verification');
        Route::post('/verify-phone', [CustomerAuthController::class, 'verifyPhone'])->name('verify-phone');

        // Login methods (OTP-based only)
        Route::post('/login-with-otp', [CustomerAuthController::class, 'loginWithOTP'])->name('login-with-otp');
        Route::post('/verify-login-otp', [CustomerAuthController::class, 'verifyLoginOTP'])->name('verify-login-otp');
    });
});

// Protected customer routes (authentication required)
Route::prefix('customer')->middleware(['auth:sanctum'])->group(function () {

    // Authentication management
    Route::prefix('auth')->name('api.customer.auth.')->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
        Route::get('/profile', [CustomerAuthController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerAuthController::class, 'updateProfile'])->name('update-profile');

        Route::delete('/delete-account', [CustomerAuthController::class, 'deleteAccount'])->name('delete-account');
    });

    // Customer profile and analytics
    Route::prefix('profile')->name('api.customer.profile.')->group(function () {
        Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
        Route::get('/overview', [CustomerController::class, 'overview'])->name('overview');
        Route::get('/statistics', [CustomerController::class, 'statistics'])->name('statistics');
        Route::get('/spending-analytics', [CustomerController::class, 'spendingAnalytics'])->name('spending-analytics');
        Route::get('/order-patterns', [CustomerController::class, 'orderPatterns'])->name('order-patterns');
        Route::get('/loyalty-status', [CustomerController::class, 'loyaltyStatus'])->name('loyalty-status');
        Route::post('/update-loyalty-points', [CustomerController::class, 'updateLoyaltyPoints'])->name('update-loyalty-points');
    });

    // Customer app functionality
    Route::prefix('app')->name('api.customer.app.')->group(function () {
        // Browse and search
        Route::get('/restaurants', [CustomerApiController::class, 'browseRestaurants'])->name('browse-restaurants');
        Route::get('/search', [CustomerApiController::class, 'search'])->name('search');
        Route::get('/categories', [CustomerApiController::class, 'getCategories'])->name('categories');
        Route::get('/restaurants/{id}', [CustomerApiController::class, 'getRestaurant'])->name('restaurant-details');

        // Orders
        Route::get('/orders', [CustomerApiController::class, 'getOrders'])->name('orders');
        Route::post('/orders', [CustomerApiController::class, 'placeOrder'])->name('place-order');
        Route::get('/orders/{id}', [CustomerApiController::class, 'getOrder'])->name('order-details');
        Route::post('/orders/{id}/cancel', [CustomerApiController::class, 'cancelOrder'])->name('cancel-order');

        // Favorites
        Route::get('/favorites', [CustomerApiController::class, 'getFavorites'])->name('favorites');
        Route::post('/favorites/{restaurantId}', [CustomerApiController::class, 'addToFavorites'])->name('add-to-favorites');
        Route::delete('/favorites/{restaurantId}', [CustomerApiController::class, 'removeFromFavorites'])->name('remove-from-favorites');

        // Addresses
        Route::get('/addresses', [CustomerApiController::class, 'getAddresses'])->name('addresses');
        Route::post('/addresses', [CustomerApiController::class, 'addAddress'])->name('add-address');
        Route::put('/addresses/{id}', [CustomerApiController::class, 'updateAddress'])->name('update-address');
        Route::delete('/addresses/{id}', [CustomerApiController::class, 'deleteAddress'])->name('delete-address');

        // Cart
        Route::get('/cart', [CustomerApiController::class, 'getCart'])->name('cart');
        Route::post('/cart', [CustomerApiController::class, 'addToCart'])->name('add-to-cart');
        Route::put('/cart', [CustomerApiController::class, 'updateCart'])->name('update-cart');
        Route::delete('/cart', [CustomerApiController::class, 'clearCart'])->name('clear-cart');

        // Reviews
        Route::get('/reviews', [CustomerApiController::class, 'getReviews'])->name('reviews');
        Route::post('/reviews', [CustomerApiController::class, 'submitReview'])->name('submit-review');
        Route::put('/reviews/{id}', [CustomerApiController::class, 'updateReview'])->name('update-review');

        // Notifications
        Route::get('/notifications', [CustomerApiController::class, 'getNotifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [CustomerApiController::class, 'markNotificationAsRead'])->name('mark-notification-read');
        Route::post('/notifications/read-all', [CustomerApiController::class, 'markAllNotificationsAsRead'])->name('mark-all-notifications-read');
    });
});

// Admin routes for customer management
Route::prefix('admin/customers')->name('api.admin.customers.')->middleware(['auth:sanctum', 'user.type:admin'])->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/analytics', [CustomerController::class, 'adminDashboard'])->name('analytics');
});
