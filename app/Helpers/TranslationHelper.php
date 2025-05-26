<?php

namespace App\Helpers;

class TranslationHelper
{
    /**
     * Get translated message with fallback support.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        $translation = __($key, $replace, $locale);
        
        // If translation is the same as key, try default language
        if ($translation === $key && $locale !== config('app.fallback_locale')) {
            $translation = __($key, $replace, config('app.fallback_locale'));
        }
        
        return $translation;
    }

    /**
     * Get all available translations for a key.
     *
     * @param string $key
     * @param array $replace
     * @return array
     */
    public static function getAllTranslations(string $key, array $replace = []): array
    {
        $supportedLocales = ['en', 'ar'];
        $translations = [];

        foreach ($supportedLocales as $locale) {
            $translations[$locale] = __($key, $replace, $locale);
        }

        return $translations;
    }

    /**
     * Get success message translations.
     *
     * @param string $operation
     * @param array $replace
     * @return string
     */
    public static function success(string $operation, array $replace = []): string
    {
        $key = "success.{$operation}";
        return self::trans($key, $replace);
    }

    /**
     * Get error message translations.
     *
     * @param string $error
     * @param array $replace
     * @return string
     */
    public static function error(string $error, array $replace = []): string
    {
        $key = "errors.{$error}";
        return self::trans($key, $replace);
    }

    /**
     * Get validation message translations.
     *
     * @param string $rule
     * @param string $attribute
     * @param array $replace
     * @return string
     */
    public static function validation(string $rule, string $attribute, array $replace = []): string
    {
        $attributeTranslation = self::trans("attributes.{$attribute}");
        $replace['attribute'] = $attributeTranslation;
        
        return self::trans("validation.{$rule}", $replace);
    }

    /**
     * Get status translations.
     *
     * @param string $status
     * @return string
     */
    public static function status(string $status): string
    {
        return self::trans("status.{$status}");
    }

    /**
     * Get common translations.
     *
     * @param string $key
     * @return string
     */
    public static function common(string $key): string
    {
        return self::trans("common.{$key}");
    }

    /**
     * Get notification translations.
     *
     * @param string $notification
     * @param array $replace
     * @return string
     */
    public static function notification(string $notification, array $replace = []): string
    {
        return self::trans("notifications.{$notification}", $replace);
    }

    /**
     * Get auth-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function auth(string $key, array $replace = []): string
    {
        return self::trans("auth.{$key}", $replace);
    }

    /**
     * Get API-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function api(string $key, array $replace = []): string
    {
        return self::trans("api.{$key}", $replace);
    }

    /**
     * Get product-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function products(string $key, array $replace = []): string
    {
        return self::trans("products.{$key}", $replace);
    }

    /**
     * Get category-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function categories(string $key, array $replace = []): string
    {
        return self::trans("categories.{$key}", $replace);
    }

    /**
     * Get order-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function orders(string $key, array $replace = []): string
    {
        return self::trans("orders.{$key}", $replace);
    }

    /**
     * Get business-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function business(string $key, array $replace = []): string
    {
        return self::trans("business.{$key}", $replace);
    }

    /**
     * Get subscription-related translations.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public static function subscription(string $key, array $replace = []): string
    {
        return self::trans("subscription.{$key}", $replace);
    }

    /**
     * Check if a translation key exists.
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public static function exists(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        $translation = __($key, [], $locale);
        
        return $translation !== $key;
    }

    /**
     * Get missing translation keys.
     *
     * @param array $keys
     * @param string|null $locale
     * @return array
     */
    public static function getMissingKeys(array $keys, ?string $locale = null): array
    {
        $missing = [];
        
        foreach ($keys as $key) {
            if (!self::exists($key, $locale)) {
                $missing[] = $key;
            }
        }
        
        return $missing;
    }
} 