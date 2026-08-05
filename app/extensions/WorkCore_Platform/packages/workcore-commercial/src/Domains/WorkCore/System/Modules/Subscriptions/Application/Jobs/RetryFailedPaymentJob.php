<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Application\Services\PaymentProcessingService;

class RetryFailedPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(BillingService $billingService, PaymentProcessingService $paymentService): void
    {
        $failedBillings = $billingService->getFailedBillings();

        foreach ($failedBillings as $billing) {
            try {
                if ($paymentService->retryPayment($billing)) {
                    $billingService->markBillingAsProcessed($billing, 'txn_' . uniqid());
                }
            } catch (\Exception $e) {
                \Log::error('Failed to retry payment', [
                    'billing_id' => $billing->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
