<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Services\ApiResponseService;
use App\Services\MockPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    protected $apiResponse;
    protected $paymentService;

    public function __construct(ApiResponseService $apiResponse, MockPaymentService $paymentService)
    {
        $this->apiResponse = $apiResponse;
        $this->paymentService = $paymentService;
    }

    /**
     * Get all available subscription plans.
     */
    public function getPlans()
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return $this->apiResponse->success(
            __('subscription.plans_retrieved'),
            SubscriptionPlanResource::collection($plans)
        );
    }

    /**
     * Get a specific subscription plan.
     */
    public function getPlan($planId)
    {
        $plan = SubscriptionPlan::active()->find($planId);

        if (!$plan) {
            return $this->apiResponse->error(__('subscription.plan_not_found'), null, 404);
        }

        return $this->apiResponse->success(
            __('subscription.plans_retrieved'),
            new SubscriptionPlanResource($plan)
        );
    }

    /**
     * Subscribe user to a plan.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'payment_method' => 'nullable|string|in:credit_card,debit_card,paypal',
            'card_number' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
            'expiry_month' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
            'expiry_year' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
            'cvv' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
            'cardholder_name' => 'required_if:payment_method,credit_card,debit_card|nullable|string',
        ], [
            'subscription_plan_id.required' => __('validation.required', ['attribute' => __('attributes.subscription_plan')]),
            'subscription_plan_id.exists' => __('validation.exists', ['attribute' => __('attributes.subscription_plan')]),
            'payment_method.in' => __('validation.in', ['attribute' => __('attributes.payment_method')]),
            'card_number.required_if' => __('validation.required_if', ['attribute' => __('attributes.card_number'), 'other' => __('attributes.payment_method'), 'value' => 'credit_card']),
            'expiry_month.required_if' => __('validation.required_if', ['attribute' => __('attributes.expiry_month'), 'other' => __('attributes.payment_method'), 'value' => 'credit_card']),
            'expiry_year.required_if' => __('validation.required_if', ['attribute' => __('attributes.expiry_year'), 'other' => __('attributes.payment_method'), 'value' => 'credit_card']),
            'cvv.required_if' => __('validation.required_if', ['attribute' => __('attributes.cvv'), 'other' => __('attributes.payment_method'), 'value' => 'credit_card']),
            'cardholder_name.required_if' => __('validation.required_if', ['attribute' => __('attributes.cardholder_name'), 'other' => __('attributes.payment_method'), 'value' => 'credit_card']),
        ]);

        if ($validator->fails()) {
            return $this->apiResponse->validationError($validator->errors());
        }

        $user = $request->user();
        $plan = SubscriptionPlan::active()->find($request->subscription_plan_id);

        if (!$plan) {
            return $this->apiResponse->error(__('subscription.plan_not_found'), null, 404);
        }

        if (!$plan->is_active) {
            return $this->apiResponse->error(__('subscription.plan_inactive'), null, 400);
        }

        // Cancel any existing active subscription
        $existingSubscription = UserSubscription::where('user_type', get_class($user))
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            $existingSubscription->cancel();
        }

        // Process payment
        if ($plan->isFree()) {
            $paymentResult = $this->paymentService->processFreeSubscription($user, $plan);
        } else {
            // Validate payment data for paid plans
            $paymentData = [
                'payment_method' => $request->payment_method ?? 'credit_card',
                'card_number' => $request->card_number,
                'expiry_month' => $request->expiry_month,
                'expiry_year' => $request->expiry_year,
                'cvv' => $request->cvv,
                'cardholder_name' => $request->cardholder_name,
                'card_last_four' => substr($request->card_number ?? '', -4),
            ];

            $validation = $this->paymentService->validatePaymentData($paymentData);
            if (!$validation['valid']) {
                return $this->apiResponse->validationError($validation['errors']);
            }

            $paymentResult = $this->paymentService->processPayment($user, $plan, $paymentData);
        }

        if (!$paymentResult['success']) {
            return $this->apiResponse->error(
                $paymentResult['message'],
                [
                    'error_code' => $paymentResult['error_code'] ?? null,
                    'transaction_id' => $paymentResult['transaction_id'] ?? null,
                ],
                400
            );
        }

        return $this->apiResponse->success(
            $paymentResult['message'],
            [
                'subscription' => new UserSubscriptionResource($paymentResult['subscription']->load('subscriptionPlan')),
                'transaction_id' => $paymentResult['transaction_id'] ?? null,
                'amount_charged' => $paymentResult['amount_charged'] ?? 0,
                'currency' => $paymentResult['currency'] ?? 'USD',
                'mock_notice' => $paymentResult['mock_notice'] ?? null,
            ]
        );
    }

    /**
     * Get user's current subscription.
     */
    public function getCurrentSubscription(Request $request)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_type', get_class($user))
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('subscriptionPlan')
            ->first();

        if (!$subscription) {
            return $this->apiResponse->success(
                __('subscription.no_active_subscription'),
                null
            );
        }

        return $this->apiResponse->success(
            __('subscription.current_plan'),
            new UserSubscriptionResource($subscription)
        );
    }

    /**
     * Get mock payment form data for testing.
     */
    public function getMockPaymentForm()
    {
        $mockData = $this->paymentService->getMockPaymentForm();

        return $this->apiResponse->success(
            __('subscription.mock_payment_notice'),
            $mockData
        );
    }

    /**
     * Cancel user's subscription.
     */
    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        $subscription = UserSubscription::where('user_type', get_class($user))
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return $this->apiResponse->error(__('subscription.no_active_subscription'), null, 404);
        }

        $subscription->cancel();

        return $this->apiResponse->success(
            __('subscription.cancelled_successfully'),
            new UserSubscriptionResource($subscription->fresh()->load('subscriptionPlan'))
        );
    }
}
