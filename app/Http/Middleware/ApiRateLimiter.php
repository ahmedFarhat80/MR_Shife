<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $key = 'api', int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $identifier = $this->resolveRequestSignature($request, $key);

        if (RateLimiter::tooManyAttempts($identifier, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($identifier);

            return $this->apiResponse->error(
                'Too many requests. Please try again later.',
                [
                    'retry_after' => $retryAfter,
                    'max_attempts' => $maxAttempts,
                    'decay_minutes' => $decayMinutes
                ],
                429
            )->header('Retry-After', $retryAfter)
             ->header('X-RateLimit-Limit', $maxAttempts)
             ->header('X-RateLimit-Remaining', 0);
        }

        RateLimiter::hit($identifier, $decayMinutes * 60);

        $response = $next($request);

        $remaining = $maxAttempts - RateLimiter::attempts($identifier);

        return $response->header('X-RateLimit-Limit', $maxAttempts)
                       ->header('X-RateLimit-Remaining', max(0, $remaining));
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $key
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $key): string
    {
        $user = $request->user();

        if ($user) {
            return $key . ':' . get_class($user) . ':' . $user->id;
        }

        return $key . ':' . $request->ip();
    }
}
