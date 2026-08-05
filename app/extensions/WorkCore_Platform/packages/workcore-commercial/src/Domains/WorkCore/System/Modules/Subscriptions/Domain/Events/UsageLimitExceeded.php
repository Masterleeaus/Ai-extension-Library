<?php

namespace WorkCore\Subscriptions\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WorkCore\Subscriptions\Domain\UsageLimit;

class UsageLimitExceeded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UsageLimit $usageLimit) {}
}
