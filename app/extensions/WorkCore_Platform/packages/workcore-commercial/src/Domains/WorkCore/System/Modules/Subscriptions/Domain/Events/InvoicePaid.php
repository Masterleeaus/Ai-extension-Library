<?php

namespace WorkCore\Subscriptions\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Domain\SubscriptionInvoice;

class InvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SubscriptionInvoice $invoice,
        public ?string $transactionId = null
    ) {}
}
