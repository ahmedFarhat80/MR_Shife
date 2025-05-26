<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'user.type' => \App\Http\Middleware\EnsureUserType::class,
            'api.rate.limit' => \App\Http\Middleware\ApiRateLimiter::class,
            'otp.rate.limit' => \App\Http\Middleware\ConditionalOTPRateLimit::class,
            'set.language' => \App\Http\Middleware\SetLanguage::class,
        ]);

        // Add API routes (rate limiting disabled)
        $middleware->api(append: [
            'set.language',
        ]);

        // Rate limiting disabled for development
    })
    ->withExceptions(function () {
        //
    })->create();
