<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Check if rate limiting is globally enabled
        $rateLimitingEnabled = config('app.rate_limiting_enabled', true);

        if ($rateLimitingEnabled) {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('login', function (Request $request) {
                return Limit::perMinute(5)->by($request->ip());
            });

            RateLimiter::for('registration', function (Request $request) {
                return Limit::perMinute(3)->by($request->ip());
            });

            RateLimiter::for('uploads', function (Request $request) {
                return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
            });
        } else {
            // Define unlimited rate limiters when rate limiting is disabled
            RateLimiter::for('api', function (Request $request) {
                return Limit::none();
            });

            RateLimiter::for('login', function (Request $request) {
                return Limit::none();
            });

            RateLimiter::for('registration', function (Request $request) {
                return Limit::none();
            });

            RateLimiter::for('uploads', function (Request $request) {
                return Limit::none();
            });
        }

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
