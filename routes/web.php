<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
// use App\Http\Controllers\Auth\MerchantAuthController;
use App\Http\Controllers\Auth\CustomerAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Merchant Authentication Routes (Web - Currently not implemented)
/*
Route::prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/login', function () {
        return view('auth.merchant.login');
    })->name('login');

    Route::post('/login', [MerchantAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [MerchantAuthController::class, 'logout'])->name('logout');

    Route::get('/register', function () {
        return view('auth.merchant.register');
    })->name('register');

    Route::post('/register', [MerchantAuthController::class, 'register'])->name('register.submit');
    Route::post('/verify', [MerchantAuthController::class, 'verifyPhone'])->name('verify');

    // Protected routes
    Route::middleware('merchant')->group(function () {
        Route::get('/dashboard', function () {
            return view('merchant.dashboard');
        })->name('dashboard');
    });
});
*/

// Customer Authentication Routes
Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/login', function () {
        return view('auth.customer.login');
    })->name('login');

    Route::post('/login', [CustomerAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

    Route::get('/register', function () {
        return view('auth.customer.register');
    })->name('register');

    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.submit');
    Route::post('/verify', [CustomerAuthController::class, 'verifyPhone'])->name('verify');

    // Protected routes
    Route::middleware('customer')->group(function () {
        Route::get('/dashboard', function () {
            return view('customer.dashboard');
        })->name('dashboard');
    });
});

// Language switch route for Filament
Route::post('/language-switch', function (Request $request) {
    $locale = $request->input('locale');

    if (in_array($locale, ['ar', 'en'])) {
        Session::put('locale', $locale);

        // Update user preference if authenticated
        if (Auth::check()) {
            Auth::user()->update(['language' => $locale]);
        }
    }

    return response()->json(['success' => true]);
})->name('language.switch');

// Storage files route with CORS headers
Route::get('/storage/{path}', function ($path) {
    // Normalize path separators
    $normalizedPath = str_replace('\\', '/', $path);
    $filePath = storage_path('app/public/' . $normalizedPath);

    if (!file_exists($filePath)) {
        abort(404, 'File not found: ' . $normalizedPath);
    }

    $mimeType = mime_content_type($filePath);

    $response = response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
    ]);

    // Add CORS headers
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

    return $response;
})->where('path', '.*')->name('storage.file');
