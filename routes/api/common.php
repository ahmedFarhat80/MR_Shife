<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Common API Routes
|--------------------------------------------------------------------------
|
| Here are the common API routes that are shared between different user types
| or are publicly accessible. These include general app information,
| configurations, and utility endpoints.
|
*/

// Public routes (no authentication required)
Route::prefix('app')->name('api.app.')->group(function () {
    Route::group([], function () {
        // App information
        Route::get('/info', function () {
            return response()->json([
                'success' => true,
                'message' => 'App information retrieved successfully',
                'data' => [
                    'app_name' => config('app.name'),
                    'version' => '1.0.0',
                    'supported_languages' => ['en', 'ar'],
                    'default_language' => config('app.locale'),
                    'api_version' => 'v1',
                ]
            ]);
        })->name('info');

        // App configuration
        Route::get('/config', function () {
            return response()->json([
                'success' => true,
                'message' => 'App configuration retrieved successfully',
                'data' => [
                    'currency' => 'USD',
                    'delivery_fee' => 5.00,
                    'minimum_order' => 10.00,
                    'tax_rate' => 0.15,
                    'service_fee_rate' => 0.05,
                ]
            ]);
        })->name('config');

        // Supported business types
        Route::get('/business-types', function () {
            return response()->json([
                'success' => true,
                'message' => 'Business types retrieved successfully',
                'data' => [
                    'restaurant' => 'Restaurant',
                    'grocery' => 'Grocery Store',
                    'pharmacy' => 'Pharmacy',
                    'electronics' => 'Electronics Store',
                    'clothing' => 'Clothing Store',
                    'bakery' => 'Bakery',
                    'cafe' => 'Cafe',
                    'fast_food' => 'Fast Food',
                    'other' => 'Other'
                ]
            ]);
        })->name('business-types');

        // Supported subscription plans
        Route::get('/subscription-plans', function () {
            return response()->json([
                'success' => true,
                'message' => 'Subscription plans retrieved successfully',
                'data' => [
                    'free' => [
                        'name' => 'Free Plan',
                        'price' => 0,
                        'features' => [
                            'Basic listing',
                            'Up to 10 menu items',
                            'Standard support'
                        ]
                    ],
                    'premium' => [
                        'name' => 'Premium Plan',
                        'monthly' => 79.99,
                        'half_year' => 35.99,
                        'annual' => 7.99,
                        'features' => [
                            'Priority listing',
                            'Unlimited menu items',
                            'Advanced analytics',
                            'Priority support',
                            'Marketing tools'
                        ]
                    ]
                ]
            ]);
        })->name('subscription-plans');
    });
});

// Utility routes
Route::prefix('utils')->name('api.utils.')->group(function () {
    Route::group([], function () {
        // Health check
        Route::get('/health', function () {
            return response()->json([
                'success' => true,
                'message' => 'API is healthy',
                'data' => [
                    'status' => 'ok',
                    'timestamp' => now()->toISOString(),
                    'uptime' => 'N/A'
                ]
            ]);
        })->name('health');

        // Server time
        Route::get('/time', function () {
            return response()->json([
                'success' => true,
                'message' => 'Server time retrieved successfully',
                'data' => [
                    'timestamp' => now()->timestamp,
                    'iso' => now()->toISOString(),
                    'timezone' => config('app.timezone'),
                ]
            ]);
        })->name('time');

        // Supported locales
        Route::get('/locales', function () {
            return response()->json([
                'success' => true,
                'message' => 'Supported locales retrieved successfully',
                'data' => [
                    'en' => 'English',
                    'ar' => 'العربية'
                ]
            ]);
        })->name('locales');
    });
});

// Image management routes (with authentication)
Route::prefix('images')->name('api.images.')->middleware(['auth:sanctum'])->group(function () {
    Route::group([], function () {
        // Upload single image
        Route::post('/upload', [App\Http\Controllers\Api\ImageController::class, 'uploadSingle'])->name('upload');
        
        // Upload image with multiple sizes
        Route::post('/upload-with-sizes', [App\Http\Controllers\Api\ImageController::class, 'uploadWithSizes'])->name('upload-with-sizes');
        
        // Delete image
        Route::delete('/delete', [App\Http\Controllers\Api\ImageController::class, 'delete'])->name('delete');
        
        // Get image information
        Route::get('/info', [App\Http\Controllers\Api\ImageController::class, 'getInfo'])->name('info');
        
        // Get image configuration (public)
        Route::get('/config', [App\Http\Controllers\Api\ImageController::class, 'getConfig'])->name('config')->withoutMiddleware(['auth:sanctum']);
    });
});

// Location services
Route::prefix('location')->name('api.location.')->group(function () {
    Route::group([], function () {
        Route::get('/cities', function () {
            return response()->json([
                'success' => true,
                'message' => 'Cities retrieved successfully',
                'data' => [
                    // Add your supported cities here
                    ['id' => 1, 'name' => 'Riyadh', 'name_ar' => 'الرياض'],
                    ['id' => 2, 'name' => 'Jeddah', 'name_ar' => 'جدة'],
                    ['id' => 3, 'name' => 'Dammam', 'name_ar' => 'الدمام'],
                ]
            ]);
        })->name('cities');

        Route::get('/areas/{city_id}', function ($cityId) {
            return response()->json([
                'success' => true,
                'message' => 'Areas retrieved successfully',
                'data' => [
                    // Add areas for the specified city
                ]
            ]);
        })->name('areas');
    });
});
