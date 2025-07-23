<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | This array contains all the locales that your application supports.
    | The key is the locale code and the value is an array containing
    | the name and script of the locale.
    |
    */
    'locales' => [
        'ar' => [
            'name' => 'العربية',
            'script' => 'Arab',
            'dir' => 'rtl',
        ],
        'en' => [
            'name' => 'English',
            'script' => 'Latn',
            'dir' => 'ltr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Flags
    |--------------------------------------------------------------------------
    |
    | This array contains the flag images for each locale. You can use
    | any image format supported by your browser. The key is the locale
    | code and the value is the path to the flag image.
    |
    */
    'flags' => [
        'ar' => 'https://flagcdn.com/w40/sa.png',
        'en' => 'https://flagcdn.com/w40/us.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Type
    |--------------------------------------------------------------------------
    |
    | This option controls how the language switch is displayed.
    | Available options: 'dropdown', 'buttons'
    |
    */
    'display_type' => 'dropdown',

    /*
    |--------------------------------------------------------------------------
    | Show Flag
    |--------------------------------------------------------------------------
    |
    | This option controls whether to show flags in the language switch.
    |
    */
    'show_flag' => true,

    /*
    |--------------------------------------------------------------------------
    | Show Name
    |--------------------------------------------------------------------------
    |
    | This option controls whether to show language names in the language switch.
    |
    */
    'show_name' => true,

    /*
    |--------------------------------------------------------------------------
    | Placement
    |--------------------------------------------------------------------------
    |
    | This option controls where the language switch is placed.
    | Available options: 'topbar', 'sidebar'
    |
    */
    'placement' => 'topbar',

    /*
    |--------------------------------------------------------------------------
    | Outside Panels
    |--------------------------------------------------------------------------
    |
    | This option controls whether the language switch should be available
    | outside of Filament panels (e.g., on login pages).
    |
    */
    'outside_panels' => true,

    /*
    |--------------------------------------------------------------------------
    | Visible
    |--------------------------------------------------------------------------
    |
    | This option controls whether the language switch is visible.
    | You can use a closure to dynamically control visibility.
    |
    */
    'visible' => true,

    /*
    |--------------------------------------------------------------------------
    | Labels
    |--------------------------------------------------------------------------
    |
    | This array contains the labels used in the language switch component.
    |
    */
    'labels' => [
        'language' => 'اللغة',
        'change_language' => 'تغيير اللغة',
    ],
];
