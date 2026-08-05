<?php

namespace WorkCore\Subscriptions\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Application\Services\PaymentProcessingService;
use WorkCore\Subscriptions\Domain\BillingSchedule;

class ProcessBillingCycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(BillingService $billingService, PaymentProcessingService $paymentService): void
    {
        $pendingBillings = $billingService->getPendingBillings();

        foreach ($pendingBillings as $billing) {
            try {
                if ($paymentService->charge($billing->subscription, $billing->amount)) {
                    $billingService->markBillingAsProcessed($billing, 'txn_' . uniqid());
                } else {
                    $billingService->markBillingAsFailed($billing, 'Payment processing failed');
                }
            } catch (\Exception $e) {
                $billingService->markBillingAsFailed($billing, $e->getMessage());
            }
        }
    }
}
