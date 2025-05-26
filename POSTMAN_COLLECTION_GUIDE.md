# 🍽️ MR Shife API - دليل Postman Collection الشامل

## 📋 نظرة عامة

هذا الدليل يوضح كيفية استخدام **Postman Collection** الشامل والمنظم لمشروع **MR Shife**. تم تنظيم جميع الـ endpoints بشكل منطقي ومنفصل لسهولة الاستخدام والاختبار.

## 📁 هيكل Collection

### 🔐 **Authentication (Passwordless)**
- **🏪 Merchant Login**: تسجيل دخول التجار بـ OTP
- **👥 Customer Login**: تسجيل دخول العملاء بـ OTP
- **🔍 Common Auth**: وظائف مشتركة (معلومات المستخدم، تسجيل الخروج)

### 👥 **Customer APIs**
- **🔐 Customer Registration & Auth**: تسجيل العملاء الجدد والمصادقة
- **👤 Customer Profile Management**: إدارة الملف الشخصي
- **📱 Customer App Features**: وظائف التطبيق (تصفح المطاعم، البحث)

### 🏪 **Merchant APIs**
- **📝 Merchant Registration (6 Steps)**: التسجيل متعدد الخطوات
- **👤 Merchant Profile Management**: إدارة الملف الشخصي

### 🖼️ **Image Management**
- رفع الصور المفردة والمتعددة
- عرض وحذف الصور

## 🚀 كيفية الاستخدام

### 1. **استيراد الملفات**
```bash
# استيراد Collection
MR_Shife_Complete_API_Collection.json

# استيراد Environment
MR_Shife_Environment.postman_environment.json
```

### 2. **إعداد Environment Variables**
- `Local_MR`: `http://localhost:8000/api`
- `language`: `ar` أو `en`
- `customer_phone`: رقم هاتف العميل للاختبار
- `merchant_phone`: رقم هاتف التاجر للاختبار

### 3. **تدفق الاختبار المقترح**

#### **للعملاء (Customers):**
1. **تسجيل جديد**: `Customer Registration & Auth > Register Customer`
2. **تحقق من الهاتف**: `Send Phone Verification` → `Verify Phone`
3. **تسجيل دخول**: `Customer Login > Send Login OTP` → `Verify Login OTP`
4. **إدارة الملف الشخصي**: `Customer Profile Management`
5. **استخدام التطبيق**: `Customer App Features`

#### **للتجار (Merchants):**
1. **الخطوة 1**: `Merchant Registration > Step 1: Basic Information`
2. **الخطوة 2**: `Step 2: Send Phone Verification` → `Step 2: Verify Phone`
3. **الخطوة 3**: `Step 3: Get Subscription Plans` → `Choose Subscription` → `Process Payment`
4. **الخطوة 4**: `Step 4: Business Information` *(formData - رفع الوثائق)*
5. **الخطوة 5**: `Step 5: Business Profile` *(formData - رفع شعار العمل)*
6. **الخطوة 6**: `Step 6: Location Information` *(التسجيل يكتمل تلقائي<|im_start|>)*
7. **التحقق من الحالة**: `Get Registration Status` *(اختياري)*

## 🔧 المميزات التقنية

### **إدارة التوكن التلقائية**
- حفظ تلقائي للـ tokens بعد تسجيل الدخول
- تنظيف التوكن عند تسجيل الخروج
- استخدام `{{auth_token}}` للـ endpoints المحمية

### **دعم متعدد اللغات**
- تبديل اللغة عبر `{{language}}` variable
- دعم العربية والإنجليزية
- ترجمة شاملة للرسائل والأخطاء

### **اختبار شامل**
- Scripts تلقائية لحفظ البيانات المهمة
- تنظيم منطقي للـ endpoints
- أمثلة واقعية للبيانات

### **📁 رفع الملفات (File Upload)**
- **Step 4 - Business Information**: يدعم رفع الوثائق التجارية
  - `work_permit`: رخصة العمل (PDF, JPG, PNG - حد أقصى 5MB)
  - `id_or_passport`: الهوية أو جواز السفر (PDF, JPG, PNG - حد أقصى 5MB)
  - `health_certificate`: شهادة صحية (PDF, JPG, PNG - حد أقصى 5MB)
- **Step 5 - Business Profile**: يدعم رفع شعار العمل
  - `business_logo`: شعار العمل (JPG, PNG - حد أقصى 2MB)
- **Image Management**: رفع صور المنتجات والمعرض
  - صور مفردة ومتعددة مع أحجام مختلفة

## 📊 إحصائيات Collection

- **إجمالي Endpoints**: 50+ endpoint
- **Customer APIs**: 25+ endpoint
- **Merchant APIs**: 20+ endpoint
- **Image Management**: 5 endpoints
- **Authentication**: 10+ endpoints

## 🎯 نصائح للاستخدام

### **1. ترتيب الاختبار**
- ابدأ بالتسجيل أو تسجيل الدخول
- استخدم الـ tokens المحفوظة تلقائياً
- اختبر الوظائف بالترتيب المنطقي

### **2. إدارة البيانات**
- استخدم أرقام هواتف مختلفة للاختبار
- احفظ الـ IDs المهمة في Environment Variables
- استخدم بيانات واقعية للاختبار

### **3. استكشاف الأخطاء**
- تحقق من الـ Console للرسائل
- راجع الـ Response للتفاصيل
- تأكد من صحة الـ tokens والـ headers

### **4. رفع الملفات**
- استخدم `formData` للـ endpoints التي تتطلب رفع ملفات
- تأكد من نوع الملف المدعوم (JPG, PNG, PDF)
- راجع حجم الملف (2MB للشعارات، 5MB للوثائق)
- اختر الملفات من جهازك عبر Postman interface

### **5. أسماء الحقول الصحيحة**
- **Step 6 Location**: استخدم `location_latitude` و `location_longitude` (وليس `latitude` و `longitude`)
- **Step 4 Business Info**: استخدم أسماء الحقول كما هي في API
- **Step 5 Business Profile**: استخدم `business_logo` لرفع الشعار

### **6. إكمال التسجيل التلقائي**
- **لا يوجد endpoint منفصل للـ "Complete Registration"**
- التسجيل يكتمل **تلقائي<|im_start|>** عند إنهاء Step 6 (Location Information)
- بعد Step 6، ستحصل على `"registration_completed": true` في الاستجابة
- يمكنك استخدام `Get Registration Status` للتحقق من حالة التسجيل

### **7. مسارات Merchant Profile Management المُصححة**
- **Get Profile**: `/merchant/profile` (GET)
- **Update Basic Info**: `/merchant/profile/basic-info` (PUT)
- **Update Business Info**: `/merchant/profile/business-info` (PUT - formData)
- **Update Business Profile**: `/merchant/profile/business-profile` (PUT - formData)
- **Update Location**: `/merchant/profile/location` (PUT)
- **Logout**: `/auth/logout` (POST - مشترك لجميع المستخدمين)

### **8. ملاحظة مهمة: لا يوجد Change Password**
- **التطبيق passwordless** - لا يستخدم كلمات مرور أساس<lemma
- **المصادقة بـ OTP فقط** عبر رقم الهاتف
- **لا حاجة لتغيير كلمة المرور** لأنها غير موجودة!

### **9. 👤 Customer Profile Management - خيارات متعددة للتحديث**
- **Get Profile**: `/customer/auth/profile` (GET)
- **Update Profile (JSON)**: `/customer/auth/profile` (PUT - JSON format للحقول الأساسية)
- **Update Profile with Avatar (FormData)**: `/customer/auth/profile` (PUT - FormData مع رفع الصورة الشخصية)
- **Update Profile with Addresses (JSON)**: `/customer/auth/profile` (PUT - ملف شخصي كامل مع العناوين)
- **Logout**: `/auth/logout` (POST - مشترك لجميع المستخدمين)
- **Delete Account**: `/customer/auth/delete-account` (DELETE)

#### **الحقول المتاحة للتحديث:**
- **الأساسية**: `name_en`, `name_ar`, `email`, `date_of_birth`, `gender`
- **الصورة الشخصية**: `avatar` (ملف صورة - JPG, PNG, max 2MB)
- **العناوين**: `addresses` (مصفوفة عناوين متعددة), `default_address` (العنوان الافتراضي)
- **التفضيلات**: `dietary_preferences`, `favorite_cuisines`
- **الإشعارات**: `notifications_enabled`, `sms_notifications`, `email_notifications`

### **10. تنظيم جديد: مجلد 🔐 Authentication (Passwordless)**
- **مجلد واحد في المستوى الرئيسي** لجميع عمليات تسجيل الدخول
- **🔑 Customer Login**: تسجيل دخول العملاء بـ OTP
- **🏪 Merchant Login**: تسجيل دخول التجار بـ OTP
- **منفصل عن Registration** لوضوح أكبر في التنظيم
- **لا يوجد تكرار** - تم حذف المجلد المكرر من داخل Customer APIs

## 🔒 الأمان

- جميع الـ endpoints المحمية تتطلب `Authorization: Bearer {{token}}`
- نظام OTP آمن مع انتهاء صلاحية (1 دقيقة)
- تشفير البيانات الحساسة
- التحقق من صحة البيانات المدخلة

## 📞 الدعم

في حالة وجود مشاكل أو استفسارات:
1. تحقق من الـ Console في Postman
2. راجع الـ Response للتفاصيل
3. تأكد من تشغيل الخادم المحلي
4. تحقق من صحة الـ Environment Variables

---

**تم إنشاء هذا Collection بعناية لضمان تجربة اختبار سلسة وشاملة لجميع وظائف مشروع MR Shife** 🎉
