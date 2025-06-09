# 📱 Postman Collection Guide - MR Shife Mobile API

## 🚀 Quick Start

### **1. Import Collection**
1. افتح Postman
2. اضغط على **Import**
3. اختر ملف `postman/Mobile_API_Collection_Updated.json`
4. اضغط **Import**

### **2. Setup Environment Variables**
قم بتعديل المتغيرات التالية في Collection Variables:

```json
{
    "Local_MR": "http://localhost:8000/api",
    "language": "en",
    "auth_token": "YOUR_AUTH_TOKEN_HERE"
}
```

## 📁 Collection Structure

### **🏠 Home & App Configuration**
- ✅ **Home Screen** - الشاشة الرئيسية
- ⚙️ **App Configuration** - إعدادات التطبيق
- 📱 **Version Check** - فحص إصدار التطبيق

### **🏪 Merchants & Products (Public)**
- 🏪 **Get All Merchants** - قائمة المطاعم مع فلترة متقدمة
- 🏪 **Get Merchant Details** - تفاصيل المطعم
- 🍕 **Get Merchant Products** - منتجات المطعم
- 📂 **Get Merchant Categories** - فئات المطعم
- 🍕 **Get Product Details** - تفاصيل المنتج
- 🔍 **Search Products** - البحث في المنتجات
- ⭐ **Get Featured Products** - المنتجات المميزة
- 🔥 **Get Popular Products** - المنتجات الشائعة
- 🌍 **Get Food Nationalities** - جنسيات الطعام

### **🛒 Cart Management (Auth Required)**
- 🛒 **Get Cart** - عرض السلة
- ➕ **Add to Cart** - إضافة للسلة
- ✏️ **Update Cart Item** - تحديث عنصر السلة
- 🗑️ **Remove Cart Item** - حذف عنصر من السلة
- 🧹 **Clear Cart** - مسح السلة
- 🎫 **Apply Coupon** - تطبيق كوبون
- ❌ **Remove Coupon** - إزالة كوبون

### **📦 Orders Management (Auth Required)**
- 📋 **Get Order History** - تاريخ الطلبات
- 🛍️ **Create Order** - إنشاء طلب جديد
- 📄 **Get Order Details** - تفاصيل الطلب
- 📍 **Track Order** - تتبع الطلب
- ❌ **Cancel Order** - إلغاء الطلب

### **❤️ Favorites Management (Auth Required)**
- ❤️ **Get Favorite Merchants** - المطاعم المفضلة
- ➕ **Add Merchant to Favorites** - إضافة للمفضلة
- ❌ **Remove Merchant from Favorites** - إزالة من المفضلة

### **📍 Addresses Management (Auth Required)**
- 📍 **Get User Addresses** - عناوين المستخدم
- ➕ **Create Address** - إنشاء عنوان جديد
- ✏️ **Update Address** - تحديث العنوان
- 🗑️ **Delete Address** - حذف العنوان
- ⭐ **Set Default Address** - تعيين عنوان افتراضي

### **👤 Profile Management (Auth Required)**
- 👤 **Get User Profile** - الملف الشخصي
- ✏️ **Update Profile** - تحديث الملف الشخصي
- 📷 **Upload Avatar** - رفع الصورة الشخصية
- 🗑️ **Delete Avatar** - حذف الصورة الشخصية

### **⭐ Reviews & Notifications (Auth Required)**
- ⭐ **Get User Reviews** - تقييمات المستخدم
- ⭐ **Review Merchant** - تقييم المطعم
- ⭐ **Review Product** - تقييم المنتج
- 🔔 **Get Notifications** - الإشعارات
- ✅ **Mark Notification as Read** - تعليم الإشعار كمقروء
- ✅ **Mark All Notifications as Read** - تعليم جميع الإشعارات كمقروءة

## 🔧 How to Test

### **1. Public Endpoints (No Auth Required)**
```bash
# Test these first - they work immediately
✅ Home Screen
✅ Get All Merchants  
✅ Get Merchant Details
✅ Search Products
✅ Get Featured Products
✅ Get Popular Products
```

### **2. Protected Endpoints (Auth Required)**
```bash
# You need to set auth_token variable first
🔒 Cart Management
🔒 Orders Management
🔒 Favorites Management
🔒 Profile Management
🔒 Addresses Management
🔒 Reviews & Notifications
```

## 🎯 Testing Examples

### **Example 1: Test Home Screen**
1. اختر **🏠 Home Screen**
2. اضغط **Send**
3. ستحصل على response مع بيانات الشاشة الرئيسية

### **Example 2: Search for Pizza**
1. اختر **🔍 Search Products**
2. في URL، غير `query=pizza` إلى ما تريد البحث عنه
3. اضغط **Send**

### **Example 3: Get Merchant Details**
1. اختر **🏪 Get Merchant Details**
2. في URL، غير `merchants/1` إلى ID المطعم المطلوب
3. اضغط **Send**

### **Example 4: Add to Cart (Requires Auth)**
1. أولاً، احصل على auth token من login endpoint
2. ضع الـ token في متغير `auth_token`
3. اختر **➕ Add to Cart**
4. عدل الـ request body حسب المنتج المطلوب
5. اضغط **Send**

## 📝 Request Body Examples

### **Add to Cart**
```json
{
    "product_id": 1,
    "quantity": 2,
    "options": [
        {
            "option_id": "size",
            "choice_id": "large"
        },
        {
            "option_id": "extras",
            "choice_id": "cheese"
        }
    ],
    "special_instructions": "No onions please"
}
```

### **Create Order**
```json
{
    "delivery_address_id": 1,
    "payment_method": "cash",
    "delivery_instructions": "Ring the doorbell",
    "scheduled_delivery_time": null,
    "coupon_code": "SAVE20"
}
```

### **Create Address**
```json
{
    "type": "home",
    "title": "Home",
    "address_line_1": "123 Main Street",
    "address_line_2": "Apartment 4B",
    "city": "Riyadh",
    "state": "Riyadh Province",
    "postal_code": "12345",
    "country": "Saudi Arabia",
    "latitude": 24.7136,
    "longitude": 46.6753,
    "is_default": true,
    "delivery_instructions": "Ring the doorbell"
}
```

### **Review Merchant**
```json
{
    "rating": 5,
    "comment": "Excellent food and service!",
    "order_id": 1
}
```

## 🔍 Query Parameters Examples

### **Merchants Filtering**
```
?search=pizza&business_type=restaurant&is_featured=true&delivery_fee_max=10&sort_by=rating&sort_order=desc&per_page=15
```

### **Products Filtering**
```
?search=burger&category_id=1&is_vegetarian=false&has_discount=true&price_min=10&price_max=50&sort_by=price&per_page=20
```

### **Search Products**
```
?query=pizza&category_id=1&merchant_id=1&price_min=15&price_max=100&per_page=20
```

## 🚨 Common Issues & Solutions

### **Issue 1: 401 Unauthorized**
**Solution:** تأكد من وضع auth token صحيح في متغير `auth_token`

### **Issue 2: 404 Not Found**
**Solution:** تأكد من أن الـ URL صحيح وأن الـ server يعمل على `localhost:8000`

### **Issue 3: 422 Validation Error**
**Solution:** تحقق من الـ request body وتأكد من أن جميع الحقول المطلوبة موجودة

### **Issue 4: Language not working**
**Solution:** تأكد من وضع `X-Language` header مع قيمة `en` أو `ar`

## 📊 Response Format

جميع الـ responses تتبع نفس التنسيق:

```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        // Response data here
    },
    "pagination": {  // Only for paginated responses
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "has_more_pages": true
    }
}
```

## 🎉 Ready to Test!

الآن يمكنك البدء في اختبار جميع الـ endpoints! ابدأ بالـ public endpoints أولاً، ثم انتقل للـ protected endpoints بعد الحصول على auth token.

**Happy Testing! 🚀**
