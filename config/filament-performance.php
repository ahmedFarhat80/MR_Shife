<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | These settings help optimize Filament performance for production.
    |
    */

    'cache' => [
        /*
        |--------------------------------------------------------------------------
        | Component Caching
        |--------------------------------------------------------------------------
        |
        | Enable component caching for better performance in production.
        |
        */
        'components' => env('FILAMENT_CACHE_COMPONENTS', true),

        /*
        |--------------------------------------------------------------------------
        | Icon Caching
        |--------------------------------------------------------------------------
        |
        | Enable icon caching for better performance.
        |
        */
        'icons' => env('FILAMENT_CACHE_ICONS', true),
    ],

    'lazy_loading' => [
        /*
        |--------------------------------------------------------------------------
        | Lazy Loading Widgets
        |--------------------------------------------------------------------------
        |
        | Enable lazy loading for widgets to improve initial page load.
        |
        */
        'widgets' => env('FILAMENT_LAZY_WIDGETS', true),

        /*
        |--------------------------------------------------------------------------
        | Lazy Loading Tables
        |--------------------------------------------------------------------------
        |
        | Enable lazy loading for table data.
        |
        */
        'tables' => env('FILAMENT_LAZY_TABLES', true),
    ],

    'database' => [
        /*
        |--------------------------------------------------------------------------
        | Query Optimization
        |--------------------------------------------------------------------------
        |
        | Settings for optimizing database queries.
        |
        */
        'eager_loading' => env('FILAMENT_EAGER_LOADING', true),
        'pagination_size' => env('FILAMENT_PAGINATION_SIZE', 25),
    ],

    'assets' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Optimization
        |--------------------------------------------------------------------------
        |
        | Settings for optimizing CSS and JS assets.
        |
        */
        'minify' => env('FILAMENT_MINIFY_ASSETS', true),
        'combine' => env('FILAMENT_COMBINE_ASSETS', true),
    ],
];
