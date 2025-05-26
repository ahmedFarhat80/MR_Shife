<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | This array contains all the languages that your application supports.
    | You can add or remove languages as needed.
    |
    */

    'supported' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'direction' => 'ltr',
            'flag' => '🇺🇸',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'direction' => 'rtl',
            'flag' => '🇸🇦',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    |
    | This is the default language that will be used when no language
    | preference is detected from the user or request headers.
    |
    */

    'default' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Language
    |--------------------------------------------------------------------------
    |
    | This is the fallback language that will be used when a translation
    | is not available in the requested language.
    |
    */

    'fallback' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Auto Detection
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic language detection from request headers.
    |
    */

    'auto_detection' => [
        'enabled' => true,
        'headers' => [
            'X-Language',      // Custom header (highest priority)
            'Accept-Language', // Standard HTTP header
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Language Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for storing user language preferences.
    |
    */

    'user_storage' => [
        'enabled' => true,
        'column' => 'language',
        'update_on_change' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Languages
    |--------------------------------------------------------------------------
    |
    | List of languages that are written from right to left.
    |
    */

    'rtl_languages' => [
        'ar', 'he', 'fa', 'ur', 'ku', 'ps', 'sd', 'yi'
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Validation
    |--------------------------------------------------------------------------
    |
    | Validation rules for language codes.
    |
    */

    'validation' => [
        'required' => false,
        'in_supported' => true,
        'max_length' => 5,
    ],

];
