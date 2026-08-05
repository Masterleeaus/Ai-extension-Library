<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Domain\BillingSchedule;

class BillingCycleFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BillingSchedule $billingSchedule,
        public string $errorMessage
    ) {}
}
