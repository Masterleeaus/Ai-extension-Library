<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Application\Services;

use WorkCore\Subscriptions\Domain\BillingSchedule;
use WorkCore\Subscriptions\Domain\Subscription;

class PaymentProcessingService
{
    /**
     * Charge a subscription
     */
    public function charge(Subscription $subscription, float $amount): bool
    {
        if (!$subscription->isActive()) {
            throw new \InvalidArgumentException('Cannot charge an inactive subscription');
        }

        try {
            // Integration point with Finance domain for actual payment processing
            // This would call the payment gateway API
            $transactionId = $this->processPaymentGateway($subscription, $amount);

            if ($transactionId) {
                event(new \WorkCore\Subscriptions\Domain\Events\PaymentSuccessful(
                    $subscription,
                    $amount,
                    $transactionId
                ));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->handlePaymentFailure($subscription, $e->getMessage());

            return false;
        }
    }

    /**
     * Process payment through gateway
     */
    private function processPaymentGateway(Subscription $subscription, float $amount): ?string
    {
        // This is a placeholder for actual payment gateway integration
        // In production, this would call Stripe, PayPal, etc.
        // For now, we return a mock transaction ID

        if (config('subscriptions.payment_method') === 'manual') {
            return 'manual_' . uniqid();
        }

        // Actual gateway integration would go here
        // throw new PaymentGatewayException('Payment gateway not configured');

        return 'txn_' . \Illuminate\Support\Str::random(32);
    }

    /**
     * Refund a payment
     */
    public function refund(Subscription $subscription, float $amount): bool
    {
        try {
            // Integration point with Finance domain for refund processing
            $refundId = $this->processRefundGateway($subscription, $amount);

            if ($refundId) {
                event(new \WorkCore\Subscriptions\Domain\Events\PaymentRefunded(
                    $subscription,
                    $amount,
                    $refundId
                ));

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->handleRefundFailure($subscription, $e->getMessage());

            return false;
        }
    }

    /**
     * Process refund through gateway
     */
    private function processRefundGateway(Subscription $subscription, float $amount): ?string
    {
        // Placeholder for refund processing
        return 'ref_' . \Illuminate\Support\Str::random(32);
    }

    /**
     * Handle payment failure
     */
    public function handlePaymentFailure(Subscription $subscription, string $errorMessage): void
    {
        // Log the failure
        \Log::error('Payment failed for subscription', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'error' => $errorMessage,
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\PaymentFailed(
            $subscription,
            $errorMessage
        ));
    }

    /**
     * Handle refund failure
     */
    private function handleRefundFailure(Subscription $subscription, string $errorMessage): void
    {
        \Log::error('Refund failed for subscription', [
            'subscription_id' => $subscription->id,
            'customer_id' => $subscription->customer_id,
            'error' => $errorMessage,
        ]);

        event(new \WorkCore\Subscriptions\Domain\Events\RefundFailed(
            $subscription,
            $errorMessage
        ));
    }

    /**
     * Retry payment with backoff
     */
    public function retryPayment(BillingSchedule $billingSchedule): bool
    {
        $backoffMinutes = $this->calculateBackoff($billingSchedule->retry_count);

        if ($billingSchedule->last_retry_at && now()->diffInMinutes($billingSchedule->last_retry_at) < $backoffMinutes) {
            return false; // Not yet time for retry
        }

        return $this->charge($billingSchedule->subscription, $billingSchedule->amount);
    }

    /**
     * Calculate exponential backoff
     */
    private function calculateBackoff(int $retryCount): int
    {
        return min(pow(2, $retryCount) * 5, 1440); // Max 24 hours
    }

    /**
     * Validate subscription for charging
     */
    public function validateSubscriptionForCharging(Subscription $subscription): bool
    {
        return $subscription->isActive() && !$subscription->isPaused();
    }
}
