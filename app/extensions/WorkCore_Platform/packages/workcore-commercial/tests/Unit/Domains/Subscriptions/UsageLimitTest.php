<?php

declare(strict_types=1);


namespace WorkCore\Subscriptions\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WorkCore\Subscriptions\Application\Services\AccessControlService;
use WorkCore\Subscriptions\Application\Services\SubscriptionService;
use WorkCore\Subscriptions\Domain\MembershipTier;
use WorkCore\Subscriptions\Domain\UsageLimit;

class UsageLimitTest extends TestCase
{
    private AccessControlService $accessControlService;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessControlService = new AccessControlService();
        $this->subscriptionService = new SubscriptionService();
    }

    /** @test */
    public function it_can_check_usage_limit()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'features_json' => [
                ['slug' => 'api_calls', 'limit' => 1000],
            ],
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);

        $hasAccess = $this->accessControlService->checkUsageLimit($subscription, 'api_calls');
        $this->assertTrue($hasAccess);
    }

    /** @test */
    public function it_can_increment_usage()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'features_json' => [
                ['slug' => 'api_calls', 'limit' => 1000],
            ],
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);

        $this->accessControlService->incrementUsage($subscription, 'api_calls', 100);

        $usage = $this->accessControlService->getUsageStatus($subscription, 'api_calls');
        $this->assertEquals(100, $usage['used']);
        $this->assertEquals(900, $usage['remaining']);
    }

    /** @test */
    public function it_detects_exceeded_usage()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'features_json' => [
                ['slug' => 'api_calls', 'limit' => 100],
            ],
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);

        $usageLimit = UsageLimit::where('subscription_id', $subscription->id)
            ->where('feature_slug', 'api_calls')
            ->first();

        $usageLimit->update(['usage_count' => 150]);

        $usage = $this->accessControlService->getUsageStatus($subscription, 'api_calls');
        $this->assertTrue($usage['exceeded']);
    }

    /** @test */
    public function it_can_reset_usage()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'features_json' => [
                ['slug' => 'api_calls', 'limit' => 1000],
            ],
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);

        $this->accessControlService->incrementUsage($subscription, 'api_calls', 500);
        $this->accessControlService->resetUsage($subscription, 'api_calls');

        $usage = $this->accessControlService->getUsageStatus($subscription, 'api_calls');
        $this->assertEquals(0, $usage['used']);
    }

    /** @test */
    public function it_can_check_feature_access()
    {
        $tenantId = 'tenant-123';
        $customerId = 'customer-456';

        $tier = new MembershipTier([
            'id' => 1,
            'name' => 'Pro',
            'price' => 99.99,
            'features_json' => [
                ['slug' => 'api_access', 'limit' => 1000],
            ],
        ]);

        $subscription = $this->subscriptionService->createSubscription($tenantId, $customerId, $tier);

        $hasAccess = $this->accessControlService->hasFeatureAccess($subscription, 'api_access');
        $this->assertTrue($hasAccess);

        $noAccess = $this->accessControlService->hasFeatureAccess($subscription, 'premium_feature');
        $this->assertFalse($noAccess);
    }
}
