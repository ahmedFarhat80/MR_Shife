<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LanguageHelper
{
    /**
     * Get supported languages.
     *
     * @return array
     */
    public static function getSupportedLanguages(): array
    {
        return ['en', 'ar'];
    }

    /**
     * Get default language.
     *
     * @return string
     */
    public static function getDefaultLanguage(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Check if language is supported.
     *
     * @param string $language
     * @return bool
     */
    public static function isSupported(string $language): bool
    {
        return in_array($language, self::getSupportedLanguages());
    }

    /**
     * Get current language.
     *
     * @return string
     */
    public static function getCurrentLanguage(): string
    {
        return App::getLocale();
    }

    /**
     * Set language for current request.
     *
     * @param string $language
     * @return void
     */
    public static function setLanguage(string $language): void
    {
        if (self::isSupported($language)) {
            App::setLocale($language);
        }
    }

    /**
     * Get language from request headers.
     *
     * @param Request $request
     * @return string|null
     */
    public static function getLanguageFromHeaders(Request $request): ?string
    {
        // Check custom X-Language header first
        $customLanguage = $request->header('X-Language');
        if ($customLanguage && self::isSupported($customLanguage)) {
            return $customLanguage;
        }

        // Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            return self::parseAcceptLanguageHeader($acceptLanguage);
        }

        return null;
    }

    /**
     * Parse Accept-Language header.
     *
     * @param string $acceptLanguage
     * @return string|null
     */
    public static function parseAcceptLanguageHeader(string $acceptLanguage): ?string
    {
        $languages = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ';q=') !== false) {
                [$lang, $quality] = explode(';q=', $part);
                $quality = (float) $quality;
            } else {
                $lang = $part;
                $quality = 1.0;
            }

            // Extract language code (e.g., 'en' from 'en-US')
            $langCode = strtolower(substr(trim($lang), 0, 2));
            
            if (self::isSupported($langCode)) {
                $languages[$langCode] = $quality;
            }
        }

        if (empty($languages)) {
            return null;
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Return the highest quality language
        return array_key_first($languages);
    }

    /**
     * Get user's preferred language.
     *
     * @param Request $request
     * @return string
     */
    public static function getUserPreferredLanguage(Request $request): string
    {
        // 1. Check if user is authenticated and has a language preference
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && isset($user->language) && self::isSupported($user->language)) {
                return $user->language;
            }
        }

        // 2. Check request headers
        $headerLanguage = self::getLanguageFromHeaders($request);
        if ($headerLanguage) {
            return $headerLanguage;
        }

        // 3. Return default language
        return self::getDefaultLanguage();
    }

    /**
     * Update user's language preference.
     *
     * @param string $language
     * @return bool
     */
    public static function updateUserLanguage(string $language): bool
    {
        if (!Auth::check() || !self::isSupported($language)) {
            return false;
        }

        $user = Auth::user();
        if ($user) {
            $user->update(['language' => $language]);
            return true;
        }

        return false;
    }

    /**
     * Get language direction (LTR or RTL).
     *
     * @param string|null $language
     * @return string
     */
    public static function getDirection(?string $language = null): string
    {
        $language = $language ?? self::getCurrentLanguage();
        
        $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
        
        return in_array($language, $rtlLanguages) ? 'rtl' : 'ltr';
    }

    /**
     * Get localized message with fallback.
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::getCurrentLanguage();
        
        $translation = __($key, $replace, $locale);
        
        // If translation is the same as key, try default language
        if ($translation === $key && $locale !== self::getDefaultLanguage()) {
            $translation = __($key, $replace, self::getDefaultLanguage());
        }
        
        return $translation;
    }
}
