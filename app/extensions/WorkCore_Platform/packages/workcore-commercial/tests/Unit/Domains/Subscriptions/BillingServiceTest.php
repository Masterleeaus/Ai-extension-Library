<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Subscriptions\Application\Services\BillingService;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;
use WorkCore\Subscriptions\Domain\BillingSchedule;
use WorkCore\Subscriptions\Domain\MembershipTier;

class BillingServiceTest extends TestCase
{
    private BillingService $billingService;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billingService = new BillingService();
        $this->subscriptionService = new SubscriptionService();
    }

    /** @test */
    public function it_can_create_billing_schedule()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);
        $billingSchedule = $this->billingService->processBillingCycle($subscription);

        $this->assertNotNull($billingSchedule->id);
        $this->assertEquals('pending', $billingSchedule->status);
        $this->assertEquals(99.99, $billingSchedule->amount);
    }

    /** @test */
    public function it_can_mark_billing_as_paid()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);
        $billingSchedule = $this->billingService->processBillingCycle($subscription);

        $this->billingService->markBillingAsProcessed($billingSchedule, 'txn_123');

        $this->assertEquals('processed', $billingSchedule->fresh()->status);
    }

    /** @test */
    public function it_can_mark_billing_as_failed()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);
        $billingSchedule = $this->billingService->processBillingCycle($subscription);

        $this->billingService->markBillingAsFailed($billingSchedule, 'Card declined');

        $this->assertEquals('failed', $billingSchedule->fresh()->status);
        $this->assertEquals('Card declined', $billingSchedule->fresh()->error_message);
    }

    /** @test */
    public function it_can_retry_failed_payment()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);
        $billingSchedule = $this->billingService->processBillingCycle($subscription);

        $billingSchedule->update(['status' => 'failed']);

        $retryable = $billingSchedule->retry();

        $this->assertTrue($retryable);
        $this->assertEquals(1, $billingSchedule->fresh()->retry_count);
    }

    /** @test */
    public function it_stops_retry_after_max_attempts()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);
        $billingSchedule = $this->billingService->processBillingCycle($subscription);

        $billingSchedule->update(['status' => 'failed', 'retry_count' => 3]);

        $retryable = $billingSchedule->retry();

        $this->assertFalse($retryable);
    }
}
