<?php

namespace App\Services;

class AdminTranslationService
{
    /**
     * Complete permission translations for all resources
     */
    public static function getPermissionTranslations(): array
    {
        return [
            // Admin permissions
            'view_admin' => 'عرض المديرين',
            'view_any_admin' => 'عرض جميع المديرين',
            'create_admin' => 'إنشاء مدير جديد',
            'update_admin' => 'تعديل المديرين',
            'delete_admin' => 'حذف المديرين',
            'delete_any_admin' => 'حذف جميع المديرين',
            'restore_admin' => 'استعادة المديرين',
            'restore_any_admin' => 'استعادة جميع المديرين',
            'replicate_admin' => 'نسخ المديرين',
            'reorder_admin' => 'إعادة ترتيب المديرين',

            // Customer permissions
            'view_customer' => 'عرض العملاء',
            'view_any_customer' => 'عرض جميع العملاء',
            'create_customer' => 'إنشاء عميل جديد',
            'update_customer' => 'تعديل العملاء',
            'delete_customer' => 'حذف العملاء',
            'delete_any_customer' => 'حذف جميع العملاء',
            'restore_customer' => 'استعادة العملاء',
            'restore_any_customer' => 'استعادة جميع العملاء',
            'force_delete_customer' => 'حذف نهائي للعملاء',
            'force_delete_any_customer' => 'حذف نهائي لجميع العملاء',

            // Merchant permissions
            'view_merchant' => 'عرض التجار',
            'view_any_merchant' => 'عرض جميع التجار',
            'create_merchant' => 'إنشاء تاجر جديد',
            'update_merchant' => 'تعديل التجار',
            'delete_merchant' => 'حذف التجار',
            'delete_any_merchant' => 'حذف جميع التجار',
            'restore_merchant' => 'استعادة التجار',
            'restore_any_merchant' => 'استعادة جميع التجار',
            'approve_merchant' => 'الموافقة على التجار',
            'suspend_merchant' => 'تعليق التجار',

            // Product permissions
            'view_product' => 'عرض المنتجات',
            'view_any_product' => 'عرض جميع المنتجات',
            'create_product' => 'إنشاء منتج جديد',
            'update_product' => 'تعديل المنتجات',
            'delete_product' => 'حذف المنتجات',
            'delete_any_product' => 'حذف جميع المنتجات',
            'restore_product' => 'استعادة المنتجات',
            'restore_any_product' => 'استعادة جميع المنتجات',
            'approve_product' => 'الموافقة على المنتجات',
            'feature_product' => 'تمييز المنتجات',

            // Order permissions
            'view_order' => 'عرض الطلبات',
            'view_any_order' => 'عرض جميع الطلبات',
            'create_order' => 'إنشاء طلب جديد',
            'update_order' => 'تعديل الطلبات',
            'delete_order' => 'حذف الطلبات',
            'delete_any_order' => 'حذف جميع الطلبات',
            'cancel_order' => 'إلغاء الطلبات',
            'refund_order' => 'استرداد الطلبات',

            // Category permissions
            'view_category' => 'عرض الفئات',
            'view_any_category' => 'عرض جميع الفئات',
            'create_category' => 'إنشاء فئة جديدة',
            'update_category' => 'تعديل الفئات',
            'delete_category' => 'حذف الفئات',
            'delete_any_category' => 'حذف جميع الفئات',
            'restore_category' => 'استعادة الفئات',
            'restore_any_category' => 'استعادة جميع الفئات',

            // Internal Category permissions
            'view_internal::category' => 'عرض الفئات الداخلية',
            'view_any_internal::category' => 'عرض جميع الفئات الداخلية',
            'create_internal::category' => 'إنشاء فئة داخلية جديدة',
            'update_internal::category' => 'تعديل الفئات الداخلية',
            'delete_internal::category' => 'حذف الفئات الداخلية',
            'delete_any_internal::category' => 'حذف جميع الفئات الداخلية',

            // Subscription Plan permissions
            'view_subscription::plan' => 'عرض خطط الاشتراك',
            'view_any_subscription::plan' => 'عرض جميع خطط الاشتراك',
            'create_subscription::plan' => 'إنشاء خطة اشتراك جديدة',
            'update_subscription::plan' => 'تعديل خطط الاشتراك',
            'delete_subscription::plan' => 'حذف خطط الاشتراك',
            'delete_any_subscription::plan' => 'حذف جميع خطط الاشتراك',

            // Role permissions
            'view_role' => 'عرض الأدوار',
            'view_any_role' => 'عرض جميع الأدوار',
            'create_role' => 'إنشاء دور جديد',
            'update_role' => 'تعديل الأدوار',
            'delete_role' => 'حذف الأدوار',
            'delete_any_role' => 'حذف جميع الأدوار',

            // Media permissions - Temporarily disabled
            // Uncomment when Media Library is needed
            // 'view_media' => 'عرض الوسائط',
            // 'view_any_media' => 'عرض جميع الوسائط',
            // 'create_media' => 'رفع وسائط جديدة',
            // 'update_media' => 'تعديل الوسائط',
            // 'delete_media' => 'حذف الوسائط',
            // 'delete_any_media' => 'حذف جميع الوسائط',

            // Food Nationality permissions
            'view_food::nationality' => 'عرض جنسيات الطعام',
            'view_any_food::nationality' => 'عرض جميع جنسيات الطعام',
            'create_food::nationality' => 'إنشاء جنسية طعام جديدة',
            'update_food::nationality' => 'تعديل جنسيات الطعام',
            'delete_food::nationality' => 'حذف جنسيات الطعام',
            'delete_any_food::nationality' => 'حذف جميع جنسيات الطعام',
        ];
    }

    /**
     * Get permission descriptions
     */
    public static function getPermissionDescriptions(): array
    {
        return [
            // Admin descriptions
            'view_admin' => 'يمكن عرض تفاصيل المديرين',
            'view_any_admin' => 'يمكن الوصول لقائمة المديرين',
            'create_admin' => 'يمكن إضافة مديرين جدد',
            'update_admin' => 'يمكن تعديل بيانات المديرين',
            'delete_admin' => 'يمكن حذف المديرين',
            'delete_any_admin' => 'يمكن حذف جميع المديرين',

            // Customer descriptions
            'view_customer' => 'يمكن عرض تفاصيل العملاء',
            'view_any_customer' => 'يمكن الوصول لقائمة العملاء',
            'create_customer' => 'يمكن إضافة عملاء جدد',
            'update_customer' => 'يمكن تعديل بيانات العملاء',
            'delete_customer' => 'يمكن حذف العملاء',
            'delete_any_customer' => 'يمكن حذف جميع العملاء',

            // Merchant descriptions
            'view_merchant' => 'يمكن عرض تفاصيل التجار',
            'view_any_merchant' => 'يمكن الوصول لقائمة التجار',
            'create_merchant' => 'يمكن إضافة تجار جدد',
            'update_merchant' => 'يمكن تعديل بيانات التجار',
            'delete_merchant' => 'يمكن حذف التجار',
            'approve_merchant' => 'يمكن الموافقة على طلبات التجار',

            // Product descriptions
            'view_product' => 'يمكن عرض تفاصيل المنتجات',
            'view_any_product' => 'يمكن الوصول لقائمة المنتجات',
            'create_product' => 'يمكن إضافة منتجات جديدة',
            'update_product' => 'يمكن تعديل بيانات المنتجات',
            'delete_product' => 'يمكن حذف المنتجات',
            'approve_product' => 'يمكن الموافقة على المنتجات',

            // Order descriptions
            'view_order' => 'يمكن عرض تفاصيل الطلبات',
            'view_any_order' => 'يمكن الوصول لقائمة الطلبات',
            'update_order' => 'يمكن تعديل حالة الطلبات',
            'cancel_order' => 'يمكن إلغاء الطلبات',
            'refund_order' => 'يمكن استرداد مبالغ الطلبات',

            // Default fallback
            'default' => 'صلاحية إدارية',
        ];
    }

    /**
     * Get permission group names with organized structure
     */
    public static function getPermissionGroups(): array
    {
        return [
            'admin' => '👥 إدارة المديرين والأدوار',
            'customer' => '🧑‍💼 إدارة العملاء',
            'merchant' => '🏪 إدارة التجار',
            'product' => '📦 إدارة المنتجات',
            'order' => '🛒 إدارة الطلبات',
            'category' => '🏷️ إدارة الفئات',
            'internal' => '📂 إدارة الفئات الداخلية',
            'nationality' => '🌍 إدارة جنسيات الطعام',
            'plan' => '💳 إدارة خطط الاشتراك',
            'role' => '🔐 إدارة الأدوار والصلاحيات',
            'media' => '🖼️ إدارة الوسائط',
            'other' => '⚙️ صلاحيات أخرى',
        ];
    }

    /**
     * Get organized permission structure for better display
     */
    public static function getOrganizedPermissions(): array
    {
        return [
            '👥 إدارة المديرين والأدوار' => [
                'view_admin' => 'عرض المديرين',
                'view_any_admin' => 'عرض جميع المديرين',
                'create_admin' => 'إنشاء مدير جديد',
                'update_admin' => 'تعديل المديرين',
                'delete_admin' => 'حذف المديرين',
                'delete_any_admin' => 'حذف جميع المديرين',
            ],
            '🧑‍💼 إدارة العملاء' => [
                'view_customer' => 'عرض العملاء',
                'view_any_customer' => 'عرض جميع العملاء',
                'create_customer' => 'إنشاء عميل جديد',
                'update_customer' => 'تعديل العملاء',
                'delete_customer' => 'حذف العملاء',
                'delete_any_customer' => 'حذف جميع العملاء',
                'restore_customer' => 'استعادة العملاء',
                'restore_any_customer' => 'استعادة جميع العملاء',
                'force_delete_customer' => 'حذف نهائي للعملاء',
                'force_delete_any_customer' => 'حذف نهائي لجميع العملاء',
            ],
            '🏪 إدارة التجار' => [
                'view_merchant' => 'عرض التجار',
                'view_any_merchant' => 'عرض جميع التجار',
                'create_merchant' => 'إنشاء تاجر جديد',
                'update_merchant' => 'تعديل التجار',
                'delete_merchant' => 'حذف التجار',
                'delete_any_merchant' => 'حذف جميع التجار',
                'restore_merchant' => 'استعادة التجار',
                'restore_any_merchant' => 'استعادة جميع التجار',
                'approve_merchant' => 'الموافقة على التجار',
                'suspend_merchant' => 'تعليق التجار',
            ],
            '📦 إدارة المنتجات' => [
                'view_product' => 'عرض المنتجات',
                'view_any_product' => 'عرض جميع المنتجات',
                'create_product' => 'إنشاء منتج جديد',
                'update_product' => 'تعديل المنتجات',
                'delete_product' => 'حذف المنتجات',
                'delete_any_product' => 'حذف جميع المنتجات',
                'restore_product' => 'استعادة المنتجات',
                'restore_any_product' => 'استعادة جميع المنتجات',
                'approve_product' => 'الموافقة على المنتجات',
                'feature_product' => 'تمييز المنتجات',
            ],
            '🛒 إدارة الطلبات' => [
                'view_order' => 'عرض الطلبات',
                'view_any_order' => 'عرض جميع الطلبات',
                'create_order' => 'إنشاء طلب جديد',
                'update_order' => 'تعديل الطلبات',
                'delete_order' => 'حذف الطلبات',
                'delete_any_order' => 'حذف جميع الطلبات',
                'cancel_order' => 'إلغاء الطلبات',
                'refund_order' => 'استرداد الطلبات',
            ],
            '🏷️ إدارة الفئات' => [
                'view_category' => 'عرض الفئات',
                'view_any_category' => 'عرض جميع الفئات',
                'create_category' => 'إنشاء فئة جديدة',
                'update_category' => 'تعديل الفئات',
                'delete_category' => 'حذف الفئات',
                'delete_any_category' => 'حذف جميع الفئات',
                'restore_category' => 'استعادة الفئات',
                'restore_any_category' => 'استعادة جميع الفئات',
            ],
            '📂 إدارة الفئات الداخلية' => [
                'view_internal::category' => 'عرض الفئات الداخلية',
                'view_any_internal::category' => 'عرض جميع الفئات الداخلية',
                'create_internal::category' => 'إنشاء فئة داخلية جديدة',
                'update_internal::category' => 'تعديل الفئات الداخلية',
                'delete_internal::category' => 'حذف الفئات الداخلية',
                'delete_any_internal::category' => 'حذف جميع الفئات الداخلية',
            ],
            '🌍 إدارة جنسيات الطعام' => [
                'view_food::nationality' => 'عرض جنسيات الطعام',
                'view_any_food::nationality' => 'عرض جميع جنسيات الطعام',
                'create_food::nationality' => 'إنشاء جنسية طعام جديدة',
                'update_food::nationality' => 'تعديل جنسيات الطعام',
                'delete_food::nationality' => 'حذف جنسيات الطعام',
                'delete_any_food::nationality' => 'حذف جميع جنسيات الطعام',
            ],
            '💳 إدارة خطط الاشتراك' => [
                'view_subscription::plan' => 'عرض خطط الاشتراك',
                'view_any_subscription::plan' => 'عرض جميع خطط الاشتراك',
                'create_subscription::plan' => 'إنشاء خطة اشتراك جديدة',
                'update_subscription::plan' => 'تعديل خطط الاشتراك',
                'delete_subscription::plan' => 'حذف خطط الاشتراك',
                'delete_any_subscription::plan' => 'حذف جميع خطط الاشتراك',
            ],
            '🔐 إدارة الأدوار والصلاحيات' => [
                'view_role' => 'عرض الأدوار',
                'view_any_role' => 'عرض جميع الأدوار',
                'create_role' => 'إنشاء دور جديد',
                'update_role' => 'تعديل الأدوار',
                'delete_role' => 'حذف الأدوار',
                'delete_any_role' => 'حذف جميع الأدوار',
            ],
            '🖼️ إدارة الوسائط' => [
                'view_media' => 'عرض الوسائط',
                'view_any_media' => 'عرض جميع الوسائط',
                'create_media' => 'رفع وسائط جديدة',
                'update_media' => 'تعديل الوسائط',
                'delete_media' => 'حذف الوسائط',
                'delete_any_media' => 'حذف جميع الوسائط',
            ],
        ];
    }

    /**
     * Translate permission name
     */
    public static function translatePermissionName(string $permission): string
    {
        $translations = self::getPermissionTranslations();
        return $translations[$permission] ?? $permission;
    }

    /**
     * Get permission description
     */
    public static function getPermissionDescription(string $permission): string
    {
        $descriptions = self::getPermissionDescriptions();
        return $descriptions[$permission] ?? $descriptions['default'];
    }

    /**
     * Get permission group name
     */
    public static function getPermissionGroupName(string $group): string
    {
        $groups = self::getPermissionGroups();
        return $groups[$group] ?? $groups['other'];
    }

    /**
     * Get common form validation messages
     */
    public static function getValidationMessages(): array
    {
        return [
            'required' => 'هذا الحقل مطلوب',
            'email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'unique' => 'هذه القيمة مستخدمة بالفعل',
            'min' => 'يجب أن يكون الحد الأدنى :min أحرف',
            'max' => 'لا يمكن أن يتجاوز :max حرف',
            'numeric' => 'يجب أن تكون القيمة رقمية',
            'confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'in' => 'القيمة المحددة غير صحيحة',
            'array' => 'يجب أن تكون القيمة مصفوفة',
            'boolean' => 'يجب أن تكون القيمة صحيحة أو خاطئة',
            'date' => 'يجب أن تكون القيمة تاريخ صحيح',
            'image' => 'يجب أن يكون الملف صورة',
            'mimes' => 'نوع الملف غير مدعوم',
            'size' => 'حجم الملف كبير جداً',
        ];
    }

    /**
     * Get common action labels
     */
    public static function getActionLabels(): array
    {
        return [
            'create' => 'إنشاء',
            'edit' => 'تعديل',
            'view' => 'عرض',
            'delete' => 'حذف',
            'save' => 'حفظ',
            'cancel' => 'إلغاء',
            'confirm' => 'تأكيد',
            'close' => 'إغلاق',
            'submit' => 'إرسال',
            'reset' => 'إعادة تعيين',
            'search' => 'بحث',
            'filter' => 'فلترة',
            'export' => 'تصدير',
            'import' => 'استيراد',
            'approve' => 'موافقة',
            'reject' => 'رفض',
            'suspend' => 'تعليق',
            'activate' => 'تفعيل',
            'deactivate' => 'إلغاء تفعيل',
        ];
    }
}
