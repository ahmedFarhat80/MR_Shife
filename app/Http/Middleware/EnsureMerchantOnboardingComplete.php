<?php

namespace App\Http\Middleware;

use App\Models\Merchant;
use App\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantOnboardingComplete
{
    protected ApiResponseService $apiResponse;

    public function __construct(ApiResponseService $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Handle an incoming request.
     * Ensures merchant has completed onboarding before accessing protected routes
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only check for merchants
        if ($user instanceof Merchant) {
            // Check if onboarding is incomplete
            if (!$this->isOnboardingComplete($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete your merchant onboarding first',
                    'data' => [
                        'redirect_to' => 'vendor_onboarding',
                        'current_step' => $this->getCurrentStep($user),
                        'next_action' => $this->getNextAction($user),
                    ]
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if merchant onboarding is complete
     */
    private function isOnboardingComplete(Merchant $merchant): bool
    {
        // Check required fields for complete onboarding
        return !empty($merchant->subscription_plan_id) &&
               !empty($merchant->business_name) &&
               in_array($merchant->status, ['active', 'pending_approval']) &&
               $merchant->registration_step === 'completed';
    }

    /**
     * Get current onboarding step
     */
    private function getCurrentStep(Merchant $merchant): string
    {
        if (empty($merchant->subscription_plan_id)) {
            return 'subscription_selection';
        }

        if (empty($merchant->business_name)) {
            return 'business_information';
        }

        return 'completion';
    }

    /**
     * Get next action for incomplete onboarding
     */
    private function getNextAction(Merchant $merchant): array
    {
        if (empty($merchant->subscription_plan_id)) {
            return [
                'step' => 1,
                'endpoint' => '/auth/merchant/onboarding/plans',
                'description' => 'Choose subscription plan'
            ];
        }

        if (empty($merchant->business_name)) {
            return [
                'step' => 2,
                'endpoint' => '/auth/merchant/onboarding/step2',
                'description' => 'Complete business information'
            ];
        }

        return [
            'step' => 'final',
            'endpoint' => '/auth/merchant/onboarding/complete',
            'description' => 'Complete onboarding process'
        ];
    }
}
