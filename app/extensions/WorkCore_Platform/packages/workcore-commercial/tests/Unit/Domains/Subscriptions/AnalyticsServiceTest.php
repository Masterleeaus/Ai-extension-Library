<?php

namespace WorkCore\Subscriptions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Subscriptions\Application\Services\AnalyticsService;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;
use WorkCore\Subscriptions\Domain\MembershipTier;

class AnalyticsServiceTest extends TestCase
{
    private AnalyticsService $analyticsService;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
        $this->subscriptionService = new SubscriptionService();
    }

    /** @test */
    public function it_can_calculate_mrr()
    {
        $tenantId = 'tenant-123';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'billing_cycle' => 'monthly',
        ]);

        $this->subscriptionService->createSubscription($tenantId, 'customer-1', $tier);
        $this->subscriptionService->createSubscription($tenantId, 'customer-2', $tier);

        $mrr = $this->analyticsService->calculateMRR($tenantId);

        $this->assertEquals(199.98, round($mrr, 2));
    }

    /** @test */
    public function it_can_calculate_arr()
    {
        $tenantId = 'tenant-123';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 100,
            'billing_cycle' => 'monthly',
        ]);

        $this->subscriptionService->createSubscription($tenantId, 'customer-1', $tier);

        $arr = $this->analyticsService->calculateARR($tenantId);

        $this->assertEquals(1200, round($arr, 0));
    }

    /** @test */
    public function it_can_get_tier_distribution()
    {
        $tenantId = 'tenant-123';

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

        $this->subscriptionService->createSubscription($tenantId, 'customer-1', $basicTier);
        $this->subscriptionService->createSubscription($tenantId, 'customer-2', $proTier);
        $this->subscriptionService->createSubscription($tenantId, 'customer-3', $proTier);

        $distribution = $this->analyticsService->getTierDistribution($tenantId);

        $this->assertEquals(1, $distribution['Basic']);
        $this->assertEquals(2, $distribution['Pro']);
    }

    /** @test */
    public function it_can_get_top_tiers()
    {
        $tenantId = 'tenant-123';

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

        $this->subscriptionService->createSubscription($tenantId, 'customer-1', $basicTier);
        $this->subscriptionService->createSubscription($tenantId, 'customer-2', $proTier);
        $this->subscriptionService->createSubscription($tenantId, 'customer-3', $proTier);

        $topTiers = $this->analyticsService->getTopTiers($tenantId, 5);

        $this->assertGreaterThan(0, count($topTiers));
        $this->assertEquals('Pro', $topTiers[0]['tier_name']);
    }

    /** @test */
    public function it_can_get_growth_metrics()
    {
        $tenantId = 'tenant-123';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
        ]);

        $this->subscriptionService->createSubscription($tenantId, 'customer-1', $tier);

        $metrics = $this->analyticsService->getGrowthMetrics($tenantId, 12);

        $this->assertIsArray($metrics);
        $this->assertGreaterThan(0, count($metrics));
    }
}
