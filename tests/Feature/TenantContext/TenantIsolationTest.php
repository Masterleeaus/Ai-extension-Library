<?php

namespace Tests\Feature\TenantContext;

use App\Domains\Shared\Context\TenantContextImpl;
use App\Domains\Shared\Context\TenantContextManager;
use App\Domains\Shared\Identity\ActorIdentity;
use App\Domains\Shared\Identity\UserIdentity;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContextManager::clearContext();
    }

    public function test_tenant_context_stores_immutable_tenant_id(): void
    {
        $tenantId = 'tenant-123';
        $context = TenantContextImpl::forService($tenantId, 'test-service');

        $this->assertEquals($tenantId, $context->getTenantId());
    }

    public function test_tenant_context_requires_tenant_id(): void
    {
        $this->expectException(\RuntimeException::class);
        TenantContextImpl::forService('', 'test-service');
    }

    public function test_tenant_context_tracks_actor_identity(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'my-service');
        $actor = $context->getActor();

        $this->assertEquals(ActorIdentity::ActorType::SERVICE, $actor->getType());
        $this->assertEquals('my-service', $actor->getId());
    }

    public function test_tenant_context_for_webhook(): void
    {
        $context = TenantContextImpl::forWebhook('tenant-123', 'stripe-webhook');
        $actor = $context->getActor();

        $this->assertEquals(ActorIdentity::ActorType::WEBHOOK, $actor->getType());
        $this->assertEquals('stripe-webhook', $actor->getId());
    }

    public function test_tenant_context_manager_stores_and_retrieves_context(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'test-service');
        TenantContextManager::setContext($context);

        $this->assertTrue(TenantContextManager::hasContext());
        $this->assertEquals('tenant-123', TenantContextManager::getTenantId());
    }

    public function test_tenant_context_manager_throws_without_context(): void
    {
        TenantContextManager::clearContext();

        $this->expectException(\RuntimeException::class);
        TenantContextManager::getContext();
    }

    public function test_permissions_checked_correctly(): void
    {
        $permissions = ['tools:execute', 'workflows:create'];
        $context = TenantContextImpl::forService(
            'tenant-123',
            'test-service',
            $permissions
        );

        $this->assertTrue($context->hasPermission('tools:execute'));
        $this->assertTrue($context->hasPermission('workflows:create'));
        $this->assertFalse($context->hasPermission('admin:access'));
    }

    public function test_different_tenants_isolated(): void
    {
        $tenant1 = TenantContextImpl::forService('tenant-1', 'service-1');
        $tenant2 = TenantContextImpl::forService('tenant-2', 'service-2');

        $this->assertNotEquals($tenant1->getTenantId(), $tenant2->getTenantId());
    }

    public function test_timestamp_is_immutable(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'service');
        $timestamp1 = $context->getTimestamp();

        sleep(1);

        $timestamp2 = $context->getTimestamp();

        $this->assertEquals($timestamp1, $timestamp2);
    }

    public function test_policy_version_tracked(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'service', [], '2.0');

        $this->assertEquals('2.0', $context->getPolicyVersion());
    }
}
