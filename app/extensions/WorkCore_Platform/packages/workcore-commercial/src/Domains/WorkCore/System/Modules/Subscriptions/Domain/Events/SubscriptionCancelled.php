<?php

namespace WorkCore\Subscriptions\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Domain\Subscription;

class SubscriptionCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public ?string $reason = null,
        public bool $refund = false
    ) {}
}
