<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Support\Str;

class MockPaymentService
{
    /**
     * Process a mock payment for subscription.
     *
     * @param mixed $user
     * @param SubscriptionPlan $plan
     * @param array $paymentData
     * @return array
     */
    public function processPayment($user, SubscriptionPlan $plan, array $paymentData = []): array
    {
        // Simulate payment processing delay
        sleep(1);

        // Mock payment success rate (95% success for testing)
        $isSuccessful = rand(1, 100) <= 95;

        if (!$isSuccessful) {
            return [
                'success' => false,
                'message' => __('subscription.payment_failed'),
                'transaction_id' => null,
                'error_code' => 'MOCK_PAYMENT_FAILED',
                'error_message' => 'Mock payment gateway returned an error'
            ];
        }

        // Generate mock transaction ID
        $transactionId = 'MOCK_' . strtoupper(Str::random(12));

        // Calculate subscription dates
        $startDate = now();
        $endDate = $this->calculateEndDate($startDate, $plan->period);

        // Create user subscription
        $subscription = UserSubscription::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount_paid' => $plan->price,
            'currency' => 'USD',
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'payment_details' => [
                'gateway' => 'mock',
                'transaction_id' => $transactionId,
                'payment_method' => $paymentData['payment_method'] ?? 'credit_card',
                'card_last_four' => $paymentData['card_last_four'] ?? '1234',
                'processed_at' => now()->toISOString(),
                'mock_data' => true
            ],
            'payment_method' => 'mock',
            'transaction_id' => $transactionId,
        ]);

        return [
            'success' => true,
            'message' => __('subscription.payment_successful'),
            'transaction_id' => $transactionId,
            'subscription' => $subscription,
            'amount_charged' => $plan->price,
            'currency' => 'USD',
            'mock_notice' => __('subscription.mock_payment_notice')
        ];
    }

    /**
     * Process a free subscription (no payment required).
     *
     * @param mixed $user
     * @param SubscriptionPlan $plan
     * @return array
     */
    public function processFreeSubscription($user, SubscriptionPlan $plan): array
    {
        // Calculate subscription dates
        $startDate = now();
        $endDate = $this->calculateEndDate($startDate, $plan->period);

        // Generate transaction ID
        $transactionId = 'FREE_' . strtoupper(Str::random(8));

        // Create user subscription
        $subscription = UserSubscription::create([
            'user_type' => get_class($user),
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount_paid' => 0,
            'currency' => 'USD',
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'payment_details' => [
                'gateway' => 'free',
                'processed_at' => now()->toISOString(),
                'free_plan' => true
            ],
            'payment_method' => 'free',
            'transaction_id' => $transactionId,
        ]);

        return [
            'success' => true,
            'message' => __('subscription.created_successfully'),
            'transaction_id' => $transactionId,
            'subscription' => $subscription,
            'amount_charged' => 0,
            'currency' => 'USD'
        ];
    }

    /**
     * Calculate subscription end date based on period.
     *
     * @param \Carbon\Carbon $startDate
     * @param string $period
     * @return \Carbon\Carbon
     */
    private function calculateEndDate($startDate, string $period)
    {
        return match($period) {
            'monthly' => $startDate->copy()->addMonth(),
            'half_year' => $startDate->copy()->addMonths(6),
            'annual' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Validate payment data for mock processing.
     *
     * @param array $paymentData
     * @return array
     */
    public function validatePaymentData(array $paymentData): array
    {
        $errors = [];

        // Mock validation rules
        if (empty($paymentData['card_number'])) {
            $errors['card_number'] = 'Card number is required';
        } elseif (strlen($paymentData['card_number']) < 16) {
            $errors['card_number'] = 'Card number must be at least 16 digits';
        }

        if (empty($paymentData['expiry_month']) || empty($paymentData['expiry_year'])) {
            $errors['expiry'] = 'Card expiry date is required';
        }

        if (empty($paymentData['cvv'])) {
            $errors['cvv'] = 'CVV is required';
        } elseif (strlen($paymentData['cvv']) < 3) {
            $errors['cvv'] = 'CVV must be at least 3 digits';
        }

        if (empty($paymentData['cardholder_name'])) {
            $errors['cardholder_name'] = 'Cardholder name is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get mock payment form data for testing.
     *
     * @return array
     */
    public function getMockPaymentForm(): array
    {
        return [
            'test_cards' => [
                [
                    'number' => '4111111111111111',
                    'brand' => 'Visa',
                    'result' => 'Success'
                ],
                [
                    'number' => '4000000000000002',
                    'brand' => 'Visa',
                    'result' => 'Declined'
                ],
                [
                    'number' => '5555555555554444',
                    'brand' => 'Mastercard',
                    'result' => 'Success'
                ]
            ],
            'test_expiry' => '12/25',
            'test_cvv' => '123',
            'test_name' => 'Test User'
        ];
    }
}
