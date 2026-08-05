<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Domain\MembershipTier;
use WorkCore\Subscriptions\Domain\Subscription;

class SubscriptionDowngraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public MembershipTier $newTier,
        public float $creditAmount
    ) {}
}
