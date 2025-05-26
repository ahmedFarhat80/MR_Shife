<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $language = $this->detectLanguage($request);
        // dd($language);
        // Set the application locale
        App::setLocale($language);

        // Store language in request for later use
        $request->attributes->set('language', $language);

        return $next($request);
    }

    /**
     * Detect the user's preferred language.
     *
     * @param Request $request
     * @return string
     */
    private function detectLanguage(Request $request): string
    {
        // Default language
        $defaultLanguage = config('app.locale', 'en');
        $supportedLanguages = ['en', 'ar'];

        // Debug information
        $debug = [
            'body_language' => $request->input('preferred_language') ?? $request->input('language'),
            'x_language_header' => $request->header('X-Language'),
            'accept_language_header' => $request->header('Accept-Language'),
            'user_authenticated' => Auth::check(),
            'user_language' => Auth::check() ? (Auth::user()->language ?? 'null') : 'not_authenticated'
        ];

        // Log debug info (remove in production)
        Log::info('Language Detection Debug:', $debug);

        // 1. Check for language in request body (for POST requests) - HIGHEST PRIORITY
        $bodyLanguage = $request->input('preferred_language') ?? $request->input('language');
        if ($bodyLanguage && in_array($bodyLanguage, $supportedLanguages)) {
            Log::info('Language detected from body:', ['language' => $bodyLanguage]);
            return $bodyLanguage;
        }

        // 2. Check for custom X-Language header
        $customLanguage = $request->header('X-Language');
        if ($customLanguage && in_array($customLanguage, $supportedLanguages)) {
            Log::info('Language detected from X-Language header:', ['language' => $customLanguage]);
            return $customLanguage;
        }

        // 3. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLanguage = $this->parseAcceptLanguage($acceptLanguage, $supportedLanguages);
            if ($preferredLanguage) {
                Log::info('Language detected from Accept-Language header:', ['language' => $preferredLanguage]);
                return $preferredLanguage;
            }
        }

        // 4. Check if user is authenticated and has a language preference (LOWER PRIORITY)
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && isset($user->language) && in_array($user->language, $supportedLanguages)) {
                Log::info('Language detected from user preference:', ['language' => $user->language]);
                return $user->language;
            }
        }

        // 5. Return default language
        Log::info('Using default language:', ['language' => $defaultLanguage]);
        return $defaultLanguage;
    }

    /**
     * Parse Accept-Language header and return the best match.
     *
     * @param string $acceptLanguage
     * @param array $supportedLanguages
     * @return string|null
     */
    private function parseAcceptLanguage(string $acceptLanguage, array $supportedLanguages): ?string
    {
        // Parse Accept-Language header
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

            if (in_array($langCode, $supportedLanguages)) {
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
     * Get the detected language from request attributes.
     *
     * @param Request $request
     * @return string
     */
    public static function getLanguage(Request $request): string
    {
        return $request->attributes->get('language', config('app.locale', 'en'));
    }
}
