<?php

namespace WorkCore\Subscriptions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;
use WorkCore\Subscriptions\Domain\MembershipTier;
use WorkCore\Subscriptions\Domain\Subscription;

class SubscriptionServiceTest extends TestCase
{
    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionService();
    }

    /** @test */
    public function it_can_create_a_subscription()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'tenant_id' => $tenantId,
            'name' => 'Pro',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
            'features_json' => [
                ['slug' => 'api_calls', 'limit' => 10000],
                ['slug' => 'users', 'limit' => 5],
            ],
        ]);

        $subscription = $this->service->createSubscription($tenantId, $customerId, $tier);

        $this->assertNotNull($subscription->id);
        $this->assertEquals($tenantId, $subscription->tenant_id);
        $this->assertEquals($customerId, $subscription->customer_id);
        $this->assertEquals('active', $subscription->status);
    }

    /** @test */
    public function it_can_upgrade_subscription()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $basicTier = new MembershipTier([
            'id' => 1,
            'name' => 'Basic',
            'price' => 29.99,
            'billing_cycle' => 'monthly',
        ]);

        $proTier = new MembershipTier([
            'id' => 2,
            'name' => 'Pro',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
        ]);

        $subscription = $this->service->createSubscription($tenantId, $customerId, $basicTier);

        $this->service->upgradeSubscription($subscription, $proTier);

        $this->assertEquals($proTier->id, $subscription->fresh()->tier_id);
    }

    /** @test */
    public function it_prevents_downgrade_to_more_expensive_tier()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $basicTier = new MembershipTier([
            'id' => 1,
            'name' => 'Basic',
            'price' => 29.99,
        ]);

        $proTier = new MembershipTier([
            'id' => 2,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->service->createSubscription($tenantId, $customerId, $proTier);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->downgradeSubscription($subscription, $basicTier);
    }

    /** @test */
    public function it_can_pause_and_resume_subscription()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->service->createSubscription($tenantId, $customerId, $tier);

        $this->service->pauseSubscription($subscription);
        $this->assertEquals('paused', $subscription->fresh()->status);

        $this->service->resumeSubscription($subscription);
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    /** @test */
    public function it_can_cancel_subscription()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->service->createSubscription($tenantId, $customerId, $tier);

        $this->service->cancelSubscription($subscription, 'User requested cancellation');

        $this->assertEquals('cancelled', $subscription->fresh()->status);
        $this->assertNotNull($subscription->fresh()->cancelled_at);
    }
}
