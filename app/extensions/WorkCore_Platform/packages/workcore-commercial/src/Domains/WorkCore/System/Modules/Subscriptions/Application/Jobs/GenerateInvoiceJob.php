<?php

namespace WorkCore\Subscriptions\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Domain\BillingSchedule;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BillingSchedule $billingSchedule)
    {
    }

    public function handle(BillingService $billingService): void
    {
        $invoice = $billingService->generateInvoice($this->billingSchedule);

        // Dispatch event or notification for invoice generation
        event(new \WorkCore\Subscriptions\Domain\Events\InvoiceGenerated($invoice));
    }
}
