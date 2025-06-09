<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\MobileApiController;

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
        Route::post('/profile', [CustomerAuthController::class, 'updateProfile'])->name('update-profile-post'); // Support for form-data uploads

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
        // Home Screen (Mobile API)
        Route::get('/home', [MobileApiController::class, 'homeScreen'])->name('home');

        // Browse and search (Mobile API)
        Route::get('/restaurants', [MobileApiController::class, 'merchants'])->name('browse-restaurants');

        // Advanced Search System (New)
        Route::get('/search', [\App\Http\Controllers\Api\SearchController::class, 'search'])->name('search');
        Route::get('/search/autocomplete', [\App\Http\Controllers\Api\SearchController::class, 'autocomplete'])->name('search-autocomplete');
        Route::get('/search/suggestions', [\App\Http\Controllers\Api\SearchController::class, 'suggestions'])->name('search-suggestions');
        Route::get('/search/history', [\App\Http\Controllers\Api\SearchController::class, 'history'])->name('search-history');
        Route::delete('/search/history/{id}', [\App\Http\Controllers\Api\SearchController::class, 'deleteHistoryItem'])->name('delete-search-history-item');
        Route::delete('/search/history', [\App\Http\Controllers\Api\SearchController::class, 'clearHistory'])->name('clear-search-history');
        Route::post('/search/record-click', [\App\Http\Controllers\Api\SearchController::class, 'recordClick'])->name('record-search-click');

        Route::get('/categories', [MobileApiController::class, 'foodNationalities'])->name('categories');
        Route::get('/restaurants/{id}', [MobileApiController::class, 'merchantDetails'])->name('restaurant-details');

        // Additional mobile endpoints
        Route::get('/merchants', [MobileApiController::class, 'merchants'])->name('merchants');
        Route::get('/merchants/{id}', [MobileApiController::class, 'merchantDetails'])->name('merchant-details');
        Route::get('/merchants/{id}/products', [MobileApiController::class, 'merchantProducts'])->name('merchant-products');
        Route::get('/merchants/{id}/categories', [MobileApiController::class, 'merchantCategories'])->name('merchant-categories');

        // Product Management Routes (New)
        Route::get('/products', [MobileApiController::class, 'allProducts'])->name('products');
        Route::get('/products/{id}', [MobileApiController::class, 'singleProduct'])->name('product-details');

        // Legacy product routes (maintained for backward compatibility)
        Route::get('/products/featured', [MobileApiController::class, 'featuredProducts'])->name('featured-products');
        Route::get('/products/popular', [MobileApiController::class, 'popularProducts'])->name('popular-products');
        // Route::get('/products/search', [MobileApiController::class, 'searchProducts'])->name('search-products'); // DEPRECATED: Use Advanced Search System instead

        // Orders (Mobile API)
        Route::get('/orders', [MobileApiController::class, 'orderHistory'])->name('orders');
        Route::post('/orders/create', [MobileApiController::class, 'createOrder'])->name('place-order');
        Route::get('/orders/{id}', [MobileApiController::class, 'getOrder'])->name('order-details');
        Route::get('/orders/{id}/track', [MobileApiController::class, 'trackOrder'])->name('track-order');
        Route::post('/orders/{id}/cancel', [MobileApiController::class, 'cancelOrder'])->name('cancel-order');

        // Favorites (Mobile API)
        Route::get('/favorites', [MobileApiController::class, 'favoriteMerchants'])->name('favorites');
        Route::post('/favorites/{merchantId}', [MobileApiController::class, 'addFavoriteMerchant'])->name('add-to-favorites');
        Route::delete('/favorites/{merchantId}', [MobileApiController::class, 'removeFavoriteMerchant'])->name('remove-from-favorites');

        // Addresses (Mobile API)
        Route::get('/addresses', [MobileApiController::class, 'getAddresses'])->name('addresses');
        Route::post('/addresses', [MobileApiController::class, 'createAddress'])->name('add-address');
        Route::put('/addresses/{id}', [MobileApiController::class, 'updateAddress'])->name('update-address');
        Route::delete('/addresses/{id}', [MobileApiController::class, 'deleteAddress'])->name('delete-address');
        Route::post('/addresses/{id}/set-default', [MobileApiController::class, 'setDefaultAddress'])->name('set-default-address');

        // Cart (Mobile API)
        Route::get('/cart', [MobileApiController::class, 'getCart'])->name('cart');
        Route::post('/cart/add', [MobileApiController::class, 'addToCart'])->name('add-to-cart');
        Route::put('/cart/update/{itemId}', [MobileApiController::class, 'updateCartItem'])->name('update-cart');
        Route::delete('/cart/remove/{itemId}', [MobileApiController::class, 'removeFromCart'])->name('remove-cart-item');
        Route::delete('/cart/clear', [MobileApiController::class, 'clearCart'])->name('clear-cart');
        Route::post('/cart/apply-coupon', [MobileApiController::class, 'applyCoupon'])->name('apply-coupon');
        Route::delete('/cart/remove-coupon', [MobileApiController::class, 'removeCoupon'])->name('remove-coupon');

        // Reviews (Mobile API)
        Route::get('/reviews', [MobileApiController::class, 'getReviews'])->name('reviews');
        Route::post('/reviews/merchants/{merchantId}', [MobileApiController::class, 'reviewMerchant'])->name('review-merchant');
        Route::post('/reviews/products/{productId}', [MobileApiController::class, 'reviewProduct'])->name('review-product');

        // Notifications (Mobile API)
        Route::get('/notifications', [MobileApiController::class, 'getNotifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [MobileApiController::class, 'markNotificationAsRead'])->name('mark-notification-read');
        Route::post('/notifications/read-all', [MobileApiController::class, 'markAllNotificationsAsRead'])->name('mark-all-notifications-read');

        // Profile (Mobile API)
        Route::get('/profile', [MobileApiController::class, 'getProfile'])->name('profile');
        Route::put('/profile/update', [MobileApiController::class, 'updateProfile'])->name('update-profile');
        Route::post('/profile/upload-avatar', [MobileApiController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('/profile/delete-avatar', [MobileApiController::class, 'deleteAvatar'])->name('delete-avatar');
    });
});

// Admin routes for customer management
Route::prefix('admin/customers')->name('api.admin.customers.')->middleware(['auth:sanctum', 'user.type:admin'])->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/analytics', [CustomerController::class, 'adminDashboard'])->name('analytics');
});


