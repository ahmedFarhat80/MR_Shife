<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get locale from various sources
        $locale = $this->getLocale($request);
        
        // Validate locale
        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Get locale from various sources
     */
    private function getLocale(Request $request): string
    {
        // 1. Check URL parameter
        if ($request->has('locale')) {
            return $request->get('locale');
        }

        // 2. Check session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. Check user preference (if authenticated)
        if (auth()->check() && auth()->user()->language) {
            return auth()->user()->language;
        }

        // 4. Check X-Language header
        if ($request->hasHeader('X-Language')) {
            return $request->header('X-Language');
        }

        // 5. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLanguage = substr($acceptLanguage, 0, 2);
            if (in_array($preferredLanguage, ['ar', 'en'])) {
                return $preferredLanguage;
            }
        }

        // 6. Default to Arabic
        return 'ar';
    }
}
