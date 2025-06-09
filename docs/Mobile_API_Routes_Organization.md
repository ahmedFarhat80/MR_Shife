# 📱 Mobile API Routes Organization

## 🎯 Overview

تم إعادة تنظيم الـ Mobile API routes لتتماشى مع هيكل التطبيق الموجود وتجنب التكرار. بدلاً من إنشاء ملف منفصل، تم توزيع الـ routes على الملفات الموجودة حسب نوع المستخدم والوظائف.

## 📁 Route Files Organization

### **routes/api/customer.php**
يحتوي على جميع الـ APIs الخاصة بالعملاء والوظائف العامة للتطبيق المحمول:

#### **🔓 Public Routes (لا تحتاج authentication)**
```php
// Browse and search (Mobile API)
Route::get('/merchants', [MobileApiController::class, 'merchants'])
Route::get('/products/search', [MobileApiController::class, 'searchProducts'])
Route::get('/categories/food-nationalities', [MobileApiController::class, 'foodNationalities'])
Route::get('/merchants/{id}', [MobileApiController::class, 'merchantDetails'])
Route::get('/merchants/{id}/products', [MobileApiController::class, 'merchantProducts'])
Route::get('/merchants/{id}/categories', [MobileApiController::class, 'merchantCategories'])
Route::get('/products/{id}', [MobileApiController::class, 'productDetails'])
Route::get('/products/featured', [MobileApiController::class, 'featuredProducts'])
Route::get('/products/popular', [MobileApiController::class, 'popularProducts'])
```

#### **🔒 Protected Routes (تحتاج authentication)**
```php
// Orders (Mobile API)
Route::get('/orders', [MobileApiController::class, 'orderHistory'])
Route::post('/orders/create', [MobileApiController::class, 'createOrder'])
Route::get('/orders/{id}', [MobileApiController::class, 'getOrder'])
Route::get('/orders/{id}/track', [MobileApiController::class, 'trackOrder'])
Route::post('/orders/{id}/cancel', [MobileApiController::class, 'cancelOrder'])

// Favorites (Mobile API)
Route::get('/favorites', [MobileApiController::class, 'favoriteMerchants'])
Route::post('/favorites/{merchantId}', [MobileApiController::class, 'addFavoriteMerchant'])
Route::delete('/favorites/{merchantId}', [MobileApiController::class, 'removeFavoriteMerchant'])

// Addresses (Mobile API)
Route::get('/addresses', [MobileApiController::class, 'getAddresses'])
Route::post('/addresses', [MobileApiController::class, 'createAddress'])
Route::put('/addresses/{id}', [MobileApiController::class, 'updateAddress'])
Route::delete('/addresses/{id}', [MobileApiController::class, 'deleteAddress'])
Route::post('/addresses/{id}/set-default', [MobileApiController::class, 'setDefaultAddress'])

// Cart (Mobile API)
Route::get('/cart', [MobileApiController::class, 'getCart'])
Route::post('/cart/add', [MobileApiController::class, 'addToCart'])
Route::put('/cart/update/{itemId}', [MobileApiController::class, 'updateCartItem'])
Route::delete('/cart/remove/{itemId}', [MobileApiController::class, 'removeFromCart'])
Route::delete('/cart/clear', [MobileApiController::class, 'clearCart'])
Route::post('/cart/apply-coupon', [MobileApiController::class, 'applyCoupon'])
Route::delete('/cart/remove-coupon', [MobileApiController::class, 'removeCoupon'])

// Reviews (Mobile API)
Route::post('/reviews/merchants/{merchantId}', [MobileApiController::class, 'reviewMerchant'])
Route::post('/reviews/products/{productId}', [MobileApiController::class, 'reviewProduct'])

// Notifications (Mobile API)
Route::get('/notifications', [MobileApiController::class, 'getNotifications'])
Route::post('/notifications/{id}/read', [MobileApiController::class, 'markNotificationAsRead'])
Route::post('/notifications/read-all', [MobileApiController::class, 'markAllNotificationsAsRead'])

// Profile (Mobile API)
Route::get('/profile', [MobileApiController::class, 'getProfile'])
Route::put('/profile/update', [MobileApiController::class, 'updateProfile'])
Route::post('/profile/upload-avatar', [MobileApiController::class, 'uploadAvatar'])
Route::delete('/profile/delete-avatar', [MobileApiController::class, 'deleteAvatar'])
```

### **routes/api/merchant.php**
يحتوي على الـ APIs الخاصة بالتجار (لم يتم تعديلها لأنها خاصة بإدارة التجار وليس التطبيق المحمول للعملاء).

### **routes/api/common.php**
يحتوي على الـ APIs العامة التي لا تحتاج authentication:

```php
// Home Screen API (Public)
Route::get('/home', [MobileApiController::class, 'homeScreen'])

// App Configuration APIs (Public)
Route::prefix('app')->group(function () {
    Route::get('/config', [MobileApiController::class, 'getAppConfig'])
    Route::post('/version-check', [MobileApiController::class, 'checkAppVersion'])
    Route::get('/features', [MobileApiController::class, 'getFeatureFlags'])
    Route::get('/maintenance', [MobileApiController::class, 'checkMaintenanceMode'])
    Route::post('/feedback', [MobileApiController::class, 'submitFeedback'])
    Route::post('/report-issue', [MobileApiController::class, 'reportIssue'])
});

// Location Services APIs (Public)
Route::prefix('location')->group(function () {
    Route::post('/nearby-merchants', [MobileApiController::class, 'getNearbyMerchants'])
    Route::get('/delivery-zones', [MobileApiController::class, 'getDeliveryZones'])
    Route::post('/check-delivery', [MobileApiController::class, 'checkDeliveryAvailability'])
    Route::post('/delivery-fee', [MobileApiController::class, 'calculateDeliveryFee'])
});

// Promotions APIs (Public)
Route::prefix('promotions')->group(function () {
    Route::get('/active', [MobileApiController::class, 'getActivePromotions'])
    Route::get('/banners', [MobileApiController::class, 'getPromotionalBanners'])
    Route::post('/validate-coupon', [MobileApiController::class, 'validateCoupon'])
});
```

## 🔄 Changes Made

### **✅ Updated Existing Routes**
- تم تحديث الـ routes الموجودة لتستخدم `MobileApiController` بدلاً من `CustomerApiController`
- تم الحفاظ على نفس أسماء الـ routes لضمان عدم كسر التطبيق
- تم إضافة وظائف جديدة مثل tracking للطلبات وإدارة الكوبونات

### **❌ Removed Duplicates**
- تم حذف الـ routes المكررة من ملف `mobile.php` المنفصل
- تم حذف الـ imports غير المستخدمة
- تم إزالة الإشارة إلى `mobile.php` من `RouteServiceProvider`

### **🎯 Improved Organization**
- تجميع الـ routes حسب نوع المستخدم والوظيفة
- اتباع نفس النمط المستخدم في التطبيق
- تجنب التكرار والتضارب

## 📋 Route Mapping

### **Customer Routes**
| Old Route | New Route | Controller Method |
|-----------|-----------|-------------------|
| `/restaurants` | `/merchants` | `merchants()` |
| `/search` | `/products/search` | `searchProducts()` |
| `/categories` | `/categories/food-nationalities` | `foodNationalities()` |
| `/restaurants/{id}` | `/merchants/{id}` | `merchantDetails()` |
| `/orders` | `/orders` | `orderHistory()` |
| `/orders` (POST) | `/orders/create` | `createOrder()` |
| `/favorites` | `/favorites` | `favoriteMerchants()` |
| `/cart` | `/cart` | `getCart()` |

### **New Routes Added**
- `/merchants/{id}/products` - منتجات التاجر
- `/merchants/{id}/categories` - فئات التاجر
- `/products/{id}` - تفاصيل المنتج
- `/products/featured` - المنتجات المميزة
- `/products/popular` - المنتجات الشائعة
- `/orders/{id}/track` - تتبع الطلب
- `/cart/apply-coupon` - تطبيق الكوبون
- `/profile/upload-avatar` - رفع الصورة الشخصية

## 🚀 Benefits

1. **Better Organization**: تنظيم أفضل حسب نوع المستخدم
2. **No Duplication**: تجنب تكرار الـ routes
3. **Consistent Naming**: أسماء متسقة مع النمط الموجود
4. **Maintainability**: سهولة الصيانة والتطوير
5. **Backward Compatibility**: الحفاظ على التوافق مع الإصدارات السابقة

## 📝 Next Steps

1. تحديث التطبيق المحمول ليستخدم الـ routes الجديدة
2. اختبار جميع الـ endpoints للتأكد من عملها
3. تحديث الـ Postman Collection
4. إضافة المزيد من الـ tests للـ Mobile API

## 🔗 Related Files

- `app/Http/Controllers/Api/MobileApiController.php`
- `app/Http/Resources/` - جميع الـ Resources
- `routes/api/customer.php`
- `routes/api/common.php`
- `postman/Mobile_API_Collection.json`
- `docs/Mobile_API_Documentation.md`
