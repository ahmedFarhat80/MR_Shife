<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| ملف الـ API الرئيسي المنظم - يشمل جميع ملفات الـ routes المنظمة
| Main organized API file - includes all organized route files
|
| 🏪 Merchant Routes - كل ما يخص التجار
| 👤 Customer Routes - كل ما يخص العملاء
| 🔧 Shared Routes - الخدمات المشتركة
|
*/

// ========================================
// 🔧 SHARED ROUTES (Images, User Info, Logout)
// ========================================
require __DIR__.'/api/shared.php';

// ========================================
// 🏪 MERCHANT ROUTES (Registration, Login, Onboarding, Profile)
// ========================================
require __DIR__.'/api/merchant.php';

// ========================================
// 👤 CUSTOMER ROUTES (Registration, Login, Profile, Shopping)
// ========================================
require __DIR__.'/api/customer.php';
