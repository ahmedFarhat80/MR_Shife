<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PasswordlessLoginController;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
|
| كل ما يخص العملاء - التسجيل، تسجيل الدخول، الملف الشخصي، التسوق، والطلبات
| All customer-related routes - registration, login, profile, shopping, orders
|
*/

// ========================================
// 🔐 CUSTOMER AUTHENTICATION (Public)
// ========================================
Route::prefix('customer')->name('customer.')->group(function () {

    // Registration & OTP
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register');
    Route::post('/verify-otp', [CustomerAuthController::class, 'verifyOTP'])->name('verify-otp');
    Route::post('/resend-otp', [CustomerAuthController::class, 'resendOTP'])->name('resend-otp');

    // Login & OTP
    Route::post('/send-login-otp', [PasswordlessLoginController::class, 'sendCustomerLoginOTP'])->name('send-login-otp');
    Route::post('/verify-login-otp', [PasswordlessLoginController::class, 'verifyCustomerLoginOTP'])->name('verify-login-otp');
});

// ========================================
// 🔒 CUSTOMER PROTECTED ROUTES
// ========================================
Route::prefix('customer')->middleware(['auth:sanctum'])->name('customer.')->group(function () {

    // ========================================
    // 👤 PROFILE MANAGEMENT
    // ========================================
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [CustomerController::class, 'profile'])->name('show');
        Route::put('/', [CustomerController::class, 'updateProfile'])->name('update');
        Route::post('/', [CustomerController::class, 'updateProfile'])->name('update-post'); // Support for form-data uploads

        // Avatar management
        Route::post('/avatar', [CustomerController::class, 'updateAvatar'])->name('update-avatar');
        Route::delete('/avatar', [CustomerController::class, 'deleteAvatar'])->name('delete-avatar');

        // Address management
        Route::get('/addresses', [CustomerController::class, 'getAddresses'])->name('addresses');
        Route::post('/addresses', [CustomerController::class, 'addAddress'])->name('add-address');
        Route::put('/addresses/{id}', [CustomerController::class, 'updateAddress'])->name('update-address');
        Route::delete('/addresses/{id}', [CustomerController::class, 'deleteAddress'])->name('delete-address');
        Route::put('/addresses/{id}/default', [CustomerController::class, 'setDefaultAddress'])->name('set-default-address');

        // Notification settings
        Route::put('/notification-settings', [CustomerController::class, 'updateNotificationSettings'])->name('notification-settings');

        // Dashboard and analytics
        Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
        Route::get('/statistics', [CustomerController::class, 'statistics'])->name('statistics');
        Route::get('/order-history', [CustomerController::class, 'orderHistory'])->name('order-history');
    });

    // ========================================
    // 🛒 SHOPPING & ORDERS
    // ========================================
    Route::prefix('shopping')->name('shopping.')->group(function () {
        // Browse products and restaurants
        Route::get('/restaurants', [CustomerController::class, 'getRestaurants'])->name('restaurants');
        Route::get('/restaurants/{id}', [CustomerController::class, 'getRestaurant'])->name('restaurant-details');
        Route::get('/products', [CustomerController::class, 'getProducts'])->name('products');
        Route::get('/products/{id}', [CustomerController::class, 'getProduct'])->name('product-details');

        // Products (matching mobile app structure)
        Route::get('/products', [CustomerController::class, 'getProducts'])->name('products'); // All products with filtering
        Route::get('/products/{id}', [CustomerController::class, 'getProduct'])->name('product-details'); // Product details
        Route::get('/search', [CustomerController::class, 'search'])->name('search'); // Search products

        // Categories (simplified - one route for all)
        Route::get('/categories', [CustomerController::class, 'getCategories'])->name('categories'); // All categories - use everywhere
        Route::get('/categories/{id}/products', [CustomerController::class, 'getCategoryProducts'])->name('category-products');

        // Cart management
        Route::get('/cart', [CustomerController::class, 'getCart'])->name('cart');
        Route::post('/cart/add', [CustomerController::class, 'addToCart'])->name('add-to-cart');
        Route::put('/cart/{id}', [CustomerController::class, 'updateCartItem'])->name('update-cart-item');
        Route::delete('/cart/{id}', [CustomerController::class, 'removeFromCart'])->name('remove-from-cart');
        Route::delete('/cart', [CustomerController::class, 'clearCart'])->name('clear-cart');

        // Checkout and orders
        Route::post('/checkout', [CustomerController::class, 'checkout'])->name('checkout');
        Route::get('/orders', [CustomerController::class, 'getOrders'])->name('orders');
        Route::get('/orders/{id}', [CustomerController::class, 'getOrder'])->name('order-details');
        Route::post('/orders/{id}/cancel', [CustomerController::class, 'cancelOrder'])->name('cancel-order');
        Route::post('/orders/{id}/review', [CustomerController::class, 'reviewOrder'])->name('review-order');

        // Favorites
        Route::get('/favorites', [CustomerController::class, 'getFavorites'])->name('favorites');
        Route::post('/favorites/{type}/{id}', [CustomerController::class, 'addToFavorites'])->name('add-to-favorites'); // type: restaurant|product
        Route::delete('/favorites/{type}/{id}', [CustomerController::class, 'removeFromFavorites'])->name('remove-from-favorites');
    });

    // ========================================
    // 🔧 SHARED ROUTES (User info, logout)
    // ========================================
    Route::get('/me', [CustomerController::class, 'profile'])->name('me');
    Route::post('/logout', [PasswordlessLoginController::class, 'logout'])->name('logout');
    Route::delete('/delete-account', [CustomerController::class, 'deleteAccount'])->name('delete-account');
});
