<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| This file now serves as the main entry point that includes all other
| API route files for better organization and maintainability.
|
*/

// Default authenticated user route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Include common/public routes
require __DIR__.'/api/common.php';

// Include authentication routes
require __DIR__.'/api/auth.php';

// Include registration routes
require __DIR__.'/api/registration.php';

// Include customer-specific routes
require __DIR__.'/api/customer.php';

// Include merchant-specific routes
require __DIR__.'/api/merchant.php';
