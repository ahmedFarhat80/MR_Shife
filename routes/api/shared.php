<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\PasswordlessLoginController;

/*
|--------------------------------------------------------------------------
| Shared API Routes
|--------------------------------------------------------------------------
|
| الخدمات المشتركة بين التجار والعملاء - إدارة الصور، معلومات المستخدم، تسجيل الخروج
| Shared services between merchants and customers - image management, user info, logout
|
*/

// ========================================
// 🖼️ IMAGE MANAGEMENT (Public)
// ========================================
Route::prefix('images')->name('images.')->group(function () {
    Route::post('/upload', [ImageController::class, 'upload'])->name('upload');
    Route::delete('/{id}', [ImageController::class, 'delete'])->name('delete');
    Route::get('/{id}', [ImageController::class, 'show'])->name('show');
});

// ========================================
// 🔧 SHARED AUTHENTICATED ROUTES
// ========================================
Route::middleware(['auth:sanctum'])->group(function () {
    
    // User info (works for both merchants and customers)
    Route::get('/me', [PasswordlessLoginController::class, 'me'])->name('me');
    
    // Logout (works for both merchants and customers)
    Route::post('/logout', [PasswordlessLoginController::class, 'logout'])->name('logout');
    
    // Image management (authenticated)
    Route::prefix('images')->name('images.')->group(function () {
        Route::get('/my-images', [ImageController::class, 'myImages'])->name('my-images');
        Route::post('/upload-avatar', [ImageController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::post('/upload-business', [ImageController::class, 'uploadBusinessImage'])->name('upload-business');
        Route::post('/upload-product', [ImageController::class, 'uploadProductImage'])->name('upload-product');
    });
});
