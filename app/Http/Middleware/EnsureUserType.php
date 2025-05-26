<?php

namespace App\Http\Middleware;

use App\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
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
     * @param  string  $userType
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $userType): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return $this->apiResponse->error('Unauthenticated.', null, 401);
            }
            return redirect()->route('login');
        }

        // Check if user is of the correct type
        $userClass = get_class($user);

        switch ($userType) {
            case 'merchant':
                if ($userClass !== 'App\Models\Merchant') {
                    if ($request->expectsJson()) {
                        return $this->apiResponse->error('Access denied. Merchant access required.', null, 403);
                    }
                    return redirect()->route('merchant.login');
                }
                break;

            case 'customer':
                if ($userClass !== 'App\Models\Customer') {
                    if ($request->expectsJson()) {
                        return $this->apiResponse->error('Access denied. Customer access required.', null, 403);
                    }
                    return redirect()->route('customer.login');
                }
                break;

            default:
                if ($request->expectsJson()) {
                    return $this->apiResponse->error('Invalid user type specified.', null, 500);
                }
                abort(500, 'Invalid user type specified.');
        }

        return $next($request);
    }
}
