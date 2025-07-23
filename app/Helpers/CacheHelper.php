<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Default cache duration in minutes
     */
    const DEFAULT_DURATION = 60; // 1 hour

    /**
     * Cache durations for different types
     */
    const DURATIONS = [
        'categories' => 30,      // 30 minutes
        'nationalities' => 30,   // 30 minutes
        'products' => 15,        // 15 minutes
        'merchants' => 60,       // 1 hour
        'customers' => 60,       // 1 hour
        'settings' => 120,       // 2 hours
        'translations' => 240,   // 4 hours
    ];

    /**
     * Get cache with automatic expiration
     */
    public static function remember(string $key, callable $callback, ?int $minutes = null): mixed
    {
        $duration = $minutes ?? self::DEFAULT_DURATION;

        return Cache::remember($key, now()->addMinutes($duration), $callback);
    }

    /**
     * Get cache for specific type
     */
    public static function rememberByType(string $type, string $key, callable $callback): mixed
    {
        $duration = self::DURATIONS[$type] ?? self::DEFAULT_DURATION;
        $fullKey = "{$type}_{$key}";

        return Cache::remember($fullKey, now()->addMinutes($duration), $callback);
    }

    /**
     * Clear cache by type
     */
    public static function clearByType(string $type): void
    {
        if (config('cache.default') === 'redis') {
            try {
                $pattern = "{$type}_*";
                $keys = Cache::getRedis()->keys(config('cache.prefix') . $pattern);

                if (!empty($keys)) {
                    $keys = array_map(function($key) {
                        return str_replace(config('cache.prefix'), '', $key);
                    }, $keys);

                    foreach ($keys as $key) {
                        Cache::forget($key);
                    }
                }
            } catch (\Exception $e) {
                // Fallback to manual clearing
                self::clearCommonKeys($type);
            }
        } else {
            // For file cache, clear specific known keys
            self::clearCommonKeys($type);
        }
    }

    /**
     * Clear common cache keys for a type
     */
    private static function clearCommonKeys(string $type): void
    {
        $commonKeys = [
            "{$type}_list",
            "{$type}_active",
            "{$type}_featured",
            "{$type}_popular",
            "{$type}_with_products",
            "{$type}_with_orders",
            "{$type}_count",
            "{$type}_paginated",
        ];

        foreach ($commonKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear categories cache
     */
    public static function clearCategories(): void
    {
        // Clear specific category cache keys
        $categoryKeys = [
            'internal_categories',
            'categories_list',
            'active_categories',
            'categories_with_products',
            'categories_count',
            'featured_categories',
            'popular_categories',
        ];

        foreach ($categoryKeys as $key) {
            Cache::forget($key);
        }

        // Clear individual category cache (loop through possible IDs)
        for ($i = 1; $i <= 1000; $i++) {
            Cache::forget('category_' . $i);
        }

        // Clear by type
        self::clearByType('categories');

        // Clear Filament cache
        self::clearFilament();
    }

    /**
     * Clear nationalities cache
     */
    public static function clearNationalities(): void
    {
        // Clear specific nationality cache keys
        $nationalityKeys = [
            'food_nationalities',
            'nationalities_list',
            'active_nationalities',
            'nationalities_with_products',
            'nationalities_count',
            'featured_nationalities',
            'popular_nationalities',
        ];

        foreach ($nationalityKeys as $key) {
            Cache::forget($key);
        }

        // Clear individual nationality cache (loop through possible IDs)
        for ($i = 1; $i <= 1000; $i++) {
            Cache::forget('nationality_' . $i);
        }

        // Clear by type
        self::clearByType('nationalities');

        // Clear Filament cache
        self::clearFilament();
    }

    /**
     * Clear products cache
     */
    public static function clearProducts(): void
    {
        Cache::forget('products_list');
        Cache::forget('featured_products');
        Cache::forget('popular_products');

        self::clearByType('products');
    }

    /**
     * Clear merchants cache
     */
    public static function clearMerchants(): void
    {
        Cache::forget('merchants_list');
        Cache::forget('active_merchants');
        Cache::forget('featured_merchants');

        self::clearByType('merchants');
    }

    /**
     * Clear all application cache
     */
    public static function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Check if current cache driver supports tagging
     */
    public static function supportsTagging(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached', 'array']);
    }

    /**
     * Clear Filament cache
     */
    public static function clearFilament(): void
    {
        if (self::supportsTagging()) {
            // Only use tags with drivers that support them
            Cache::tags(['filament', 'categories', 'products', 'merchants'])->flush();
        } else {
            // For file cache, clear specific Filament keys
            $filamentKeys = [
                'filament_navigation',
                'filament_resources',
                'filament_widgets',
                'filament_pages',
                'filament_translations',
            ];

            foreach ($filamentKeys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        try {
            $cacheDir = storage_path('framework/cache/data');
            $files = glob($cacheDir . '/*');

            return [
                'total_files' => count($files),
                'total_size' => self::formatBytes(array_sum(array_map('filesize', $files))),
                'cache_driver' => config('cache.default'),
                'cache_prefix' => config('cache.prefix'),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to get cache statistics',
                'cache_driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
