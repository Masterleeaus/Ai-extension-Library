<?php

namespace WorkCore\Subscriptions\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use WorkCore\Subscriptions\Domain\BillingSchedule;
use WorkCore\Subscriptions\Domain\Subscription;
use WorkCore\Subscriptions\Domain\SubscriptionInvoice;

class BillingService
{
    /**
     * Process billing cycle for a subscription
     */
    public function processBillingCycle(Subscription $subscription): BillingSchedule
    {
        $nextBillingDate = $this->calculateNextBillingDate($subscription);

        $billingSchedule = BillingSchedule::create([
            'tenant_id' => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'next_billing_date' => $nextBillingDate,
            'amount' => $subscription->tier->price,
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        return $billingSchedule;
    }

    /**
     * Get pending billings for processing
     */
    public function getPendingBillings(string $tenantId = null): Collection
    {
        $query = BillingSchedule::pending();

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        return $query->with('subscription.tier')->get();
    }

    /**
     * Retry failed payment
     */
    public function retryFailedPayment(BillingSchedule $billingSchedule): bool
    {
        if ($billingSchedule->status !== 'failed') {
            throw new \InvalidArgumentException('Cannot retry non-failed billing schedule');
        }

        if (!$billingSchedule->retry()) {
            return false;
        }

        $paymentService = app(PaymentProcessingService::class);

        return $paymentService->charge(
            $billingSchedule->subscription,
            $billingSchedule->amount
        );
    }

    /**
     * Get failed billings that need retry
     */
    public function getFailedBillings(string $tenantId = null): Collection
    {
        $query = BillingSchedule::failed();

        if ($tenantId) {
            $query->forTenant($tenantId);
        }

        return $query->with('subscription.tier')->get();
    }

    /**
     * Calculate next billing date
     */
    private function calculateNextBillingDate(Subscription $subscription): Carbon
    {
        $lastCycle = $subscription->cycles()->orderByDesc('id')->first();

        if (!$lastCycle) {
            return now()->addMonth();
        }

        return Carbon::parse($lastCycle->end_date)->addDay();
    }

    /**
     * Generate invoice for billing schedule
     */
    public function generateInvoice(BillingSchedule $billingSchedule): SubscriptionInvoice
    {
        return $billingSchedule->generateInvoice();
    }

    /**
     * Send billing notification
     */
    public function sendBillingNotification(BillingSchedule $billingSchedule, string $type = 'upcoming'): void
    {
        $subscription = $billingSchedule->subscription;
        $customer = $subscription->customer_id;

        // Dispatch notification event
        event(new \WorkCore\Subscriptions\Domain\Events\BillingNotification(
            $subscription,
            $billingSchedule,
            $type
        ));
    }

    /**
     * Send payment reminder
     */
    public function sendPaymentReminder(SubscriptionInvoice $invoice): void
    {
        if (!$invoice->isOverdue()) {
            throw new \InvalidArgumentException('Invoice is not overdue');
        }

        event(new \WorkCore\Subscriptions\Domain\Events\PaymentReminder($invoice));
    }

    /**
     * Mark billing as processed
     */
    public function markBillingAsProcessed(BillingSchedule $billingSchedule, string $transactionId): void
    {
        $billingSchedule->markAsPaid($transactionId);

        $invoice = $this->generateInvoice($billingSchedule);
        $invoice->markAsPaid($transactionId);
    }

    /**
     * Mark billing as failed
     */
    public function markBillingAsFailed(BillingSchedule $billingSchedule, string $errorMessage): void
    {
        $billingSchedule->markAsFailed($errorMessage);
    }

    /**
     * Get billing history for subscription
     */
    public function getBillingHistory(Subscription $subscription, int $limit = 12): Collection
    {
        return $subscription->billingSchedules()
            ->orderByDesc('next_billing_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get unpaid invoices
     */
    public function getUnpaidInvoices(Subscription $subscription): Collection
    {
        return $subscription->invoices()
            ->whereIn('status', ['issued', 'overdue'])
            ->orderBy('due_date')
            ->get();
    }
}
