<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ConditionalOTPRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '1'): Response
    {
        // Check if OTP rate limiting is enabled
        $isEnabled = config('app.otp_rate_limiting_enabled', true);

        if (!$isEnabled) {
            // Skip rate limiting if disabled
            return $next($request);
        }

        // Apply rate limiting if enabled
        $throttle = new ThrottleRequests();
        return $throttle->handle($request, $next, $maxAttempts, $decayMinutes);
    }
}
