<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url' => ':attribute ليس رابط صحيح.',
    'after' => 'يجب أن يكون :attribute تاريخ بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخ بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'ascii' => 'يجب أن يحتوي :attribute على أحرف وأرقام ورموز أحادية البايت فقط.',
    'before' => 'يجب أن يكون :attribute تاريخ قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخ قبل أو يساوي :date.',
    'between' => [
        'array' => 'يجب أن يحتوي :attribute على عناصر بين :min و :max.',
        'file' => 'يجب أن يكون :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute بين :min و :max.',
        'string' => 'يجب أن يكون :attribute بين :min و :max حرف.',
    ],
    'boolean' => 'يجب أن يكون حقل :attribute صحيح أو خاطئ.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخ صحيح.',
    'date_equals' => 'يجب أن يكون :attribute تاريخ يساوي :date.',
    'date_format' => ':attribute لا يطابق التنسيق :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منازل عشرية.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits' => 'يجب أن يكون :attribute :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'dimensions' => ':attribute له أبعاد صورة غير صحيحة.',
    'distinct' => 'حقل :attribute له قيمة مكررة.',
    'doesnt_end_with' => 'يجب ألا ينتهي :attribute بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ :attribute بأحد القيم التالية: :values.',
    'email' => 'يجب أن يكون :attribute عنوان بريد إلكتروني صحيح.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values.',
    'enum' => ':attribute المحدد غير صحيح.',
    'exists' => ':attribute المحدد غير صحيح.',
    'file' => 'يجب أن يكون :attribute ملف.',
    'filled' => 'يجب أن يحتوي حقل :attribute على قيمة.',
    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
        'string' => 'يجب أن يكون :attribute أكبر من :value حرف.',
    ],
    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عنصر أو أكثر.',
        'file' => 'يجب أن يكون :attribute أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أكبر من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أكبر من أو يساوي :value حرف.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => ':attribute المحدد غير صحيح.',
    'in_array' => 'حقل :attribute غير موجود في :other.',
    'integer' => 'يجب أن يكون :attribute رقم صحيح.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيح.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيح.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيح.',
    'json' => 'يجب أن يكون :attribute نص JSON صحيح.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',
    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصر.',
        'file' => 'يجب أن يكون :attribute أقل من :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
        'string' => 'يجب أن يكون :attribute أقل من :value حرف.',
    ],
    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عنصر.',
        'file' => 'يجب أن يكون :attribute أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute أقل من أو يساوي :value.',
        'string' => 'يجب أن يكون :attribute أقل من أو يساوي :value حرف.',
    ],
    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صحيح.',
    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصر.',
        'file' => 'يجب ألا يكون :attribute أكبر من :max كيلوبايت.',
        'numeric' => 'يجب ألا يكون :attribute أكبر من :max.',
        'string' => 'يجب ألا يكون :attribute أكبر من :max حرف.',
    ],
    'max_digits' => 'يجب ألا يحتوي :attribute على أكثر من :max رقم.',
    'mimes' => 'يجب أن يكون :attribute ملف من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملف من نوع: :values.',
    'min' => [
        'array' => 'يجب أن يحتوي :attribute على الأقل على :min عنصر.',
        'file' => 'يجب أن يكون :attribute على الأقل :min كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute على الأقل :min.',
        'string' => 'يجب أن يكون :attribute على الأقل :min حرف.',
    ],
    'min_digits' => 'يجب أن يحتوي :attribute على الأقل على :min رقم.',
    'missing' => 'يجب أن يكون حقل :attribute مفقود.',
    'missing_if' => 'يجب أن يكون حقل :attribute مفقود عندما يكون :other هو :value.',
    'missing_unless' => 'يجب أن يكون حقل :attribute مفقود إلا إذا كان :other في :values.',
    'missing_with' => 'يجب أن يكون حقل :attribute مفقود عندما يكون :values موجود.',
    'missing_with_all' => 'يجب أن يكون حقل :attribute مفقود عندما تكون :values موجودة.',
    'multiple_of' => 'يجب أن يكون :attribute مضاعف لـ :value.',
    'not_in' => ':attribute المحدد غير صحيح.',
    'not_regex' => 'تنسيق :attribute غير صحيح.',
    'numeric' => 'يجب أن يكون :attribute رقم.',
    'password' => 'كلمة المرور غير صحيحة.',
    'present' => 'يجب أن يكون حقل :attribute موجود.',
    'prohibited' => 'حقل :attribute محظور.',
    'prohibited_if' => 'حقل :attribute محظور عندما يكون :other هو :value.',
    'prohibited_unless' => 'حقل :attribute محظور إلا إذا كان :other في :values.',
    'prohibits' => 'حقل :attribute يمنع :other من الوجود.',
    'regex' => 'تنسيق :attribute غير صحيح.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي حقل :attribute على مدخلات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عندما يتم قبول :other.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other في :values.',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجود.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجود.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'يجب أن يتطابق :attribute و :other.',
    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصر.',
        'file' => 'يجب أن يكون :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن يكون :attribute :size.',
        'string' => 'يجب أن يكون :attribute :size حرف.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values.',
    'string' => 'يجب أن يكون :attribute نص.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صحيحة.',
    'unique' => ':attribute مُستخدم من قبل.',
    'uploaded' => 'فشل في رفع :attribute.',
    'uppercase' => 'يجب أن يكون :attribute بأحرف كبيرة.',
    'url' => 'يجب أن يكون :attribute رابط صحيح.',
    'ulid' => 'يجب أن يكون :attribute ULID صحيح.',
    'uuid' => 'يجب أن يكون :attribute UUID صحيح.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "rule.attribute" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    // Product filtering validation messages
    'category_not_found' => 'الفئة المحددة غير موجودة.',
    'food_nationality_not_found' => 'جنسية الطعام المحددة غير موجودة.',
    'merchant_not_found' => 'المطعم المحدد غير موجود.',
    'max_price_greater_than_min' => 'يجب أن يكون الحد الأقصى للسعر أكبر من أو يساوي الحد الأدنى.',
    'per_page_max_50' => 'لا يمكن أن يتجاوز عدد العناصر في الصفحة 50 عنصر.',
    'invalid_sort_option' => 'خيار الترتيب المحدد غير صحيح.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name_en' => 'الاسم (بالإنجليزية)',
        'name_ar' => 'الاسم (بالعربية)',
        'phone_number' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'code' => 'رمز التحقق',
        'business_name_en' => 'اسم العمل (بالإنجليزية)',
        'business_name_ar' => 'اسم العمل (بالعربية)',
        'business_address_en' => 'عنوان العمل (بالإنجليزية)',
        'business_address_ar' => 'عنوان العمل (بالعربية)',
        'business_type' => 'نوع العمل',
        'location_latitude' => 'خط العرض',
        'location_longitude' => 'خط الطول',
        'subscription_plan' => 'خطة الاشتراك',
        'subscription_period' => 'فترة الاشتراك',
        'preferred_language' => 'اللغة المفضلة',
        'language' => 'اللغة',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'address' => 'العنوان',
        'address_en' => 'العنوان (بالإنجليزية)',
        'address_ar' => 'العنوان (بالعربية)',
        'search_query' => 'استعلام البحث',
        'search_type' => 'نوع البحث',
        'per_page' => 'عدد العناصر في الصفحة',
        'category' => 'الفئة',
        'food_nationality' => 'جنسية الطعام',
        'merchant' => 'المطعم',
        'minimum_price' => 'الحد الأدنى للسعر',
        'maximum_price' => 'الحد الأقصى للسعر',
        'city' => 'المدينة',
        'area' => 'المنطقة',
        'latitude' => 'خط العرض',
        'longitude' => 'خط الطول',
        'search_radius' => 'نطاق البحث',
        'sort_field' => 'حقل الترتيب',
        'sort_order' => 'ترتيب الفرز',
        'limit' => 'الحد الأقصى',
        'autocomplete_type' => 'نوع الإكمال التلقائي',
    ],

    // رسائل التحقق من البحث
    'search_query_required' => 'استعلام البحث مطلوب',
    'search_query_min_length' => 'يجب أن يكون استعلام البحث حرفين على الأقل',
    'search_query_max_length' => 'لا يمكن أن يتجاوز استعلام البحث 100 حرف',
    'search_query_must_be_string' => 'يجب أن يكون استعلام البحث نص',
    'invalid_search_type' => 'نوع بحث غير صالح. يجب أن يكون: الكل، المنتجات، أو المطاعم',
    'per_page_must_be_integer' => 'عدد العناصر في الصفحة يجب أن يكون رقم',
    'per_page_min_value' => 'عدد العناصر في الصفحة يجب أن يكون 1 على الأقل',
    'per_page_max_value' => 'عدد العناصر في الصفحة لا يمكن أن يتجاوز 50',
    'category_not_found' => 'الفئة المحددة غير موجودة',
    'food_nationality_not_found' => 'جنسية الطعام المحددة غير موجودة',
    'merchant_not_found' => 'المطعم المحدد غير موجود',
    'price_min_must_be_numeric' => 'الحد الأدنى للسعر يجب أن يكون رقم',
    'price_max_must_be_numeric' => 'الحد الأقصى للسعر يجب أن يكون رقم',
    'price_max_must_be_greater_than_min' => 'الحد الأقصى للسعر يجب أن يكون أكبر من الحد الأدنى',
    'invalid_business_type' => 'نوع عمل غير صالح',
    'invalid_latitude' => 'خط العرض يجب أن يكون بين -90 و 90',
    'invalid_longitude' => 'خط الطول يجب أن يكون بين -180 و 180',
    'radius_min_value' => 'نطاق البحث يجب أن يكون 1 كم على الأقل',
    'radius_max_value' => 'نطاق البحث لا يمكن أن يتجاوز 50 كم',
    'invalid_sort_field' => 'حقل ترتيب غير صالح',
    'invalid_sort_order' => 'ترتيب الفرز يجب أن يكون تصاعدي أو تنازلي',
    'limit_must_be_integer' => 'الحد الأقصى يجب أن يكون رقم',
    'limit_min_value' => 'الحد الأقصى يجب أن يكون 1 على الأقل',
    'limit_max_value' => 'الحد الأقصى لا يمكن أن يتجاوز 20',
    'invalid_autocomplete_type' => 'نوع إكمال تلقائي غير صالح',

];
