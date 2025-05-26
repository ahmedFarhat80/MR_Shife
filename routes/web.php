<?php

use Illuminate\Support\Facades\Route;
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
