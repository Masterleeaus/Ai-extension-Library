<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Domains\WorkCore\System\Context\TenantContextSnapshot;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TenantContextTest extends TestCase
{
    public function test_tenant_context_starts_without_tenant(): void
    {
        $context = new TenantContext();

        $this->assertFalse($context->hasTenant());
    }

    public function test_tenant_context_can_be_set(): void
    {
        $context = new TenantContext();
        $context->set(123, 456);

        $this->assertTrue($context->hasTenant());
        $this->assertSame(123, $context->companyId());
        $this->assertSame(456, $context->userId());
    }

    public function test_tenant_context_throws_when_accessing_company_id_without_tenant(): void
    {
        $context = new TenantContext();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('WorkCore tenant context has not been resolved');

        $context->companyId();
    }

    public function test_tenant_context_user_id_can_be_null(): void
    {
        $context = new TenantContext();
        $context->set(123);

        $this->assertNull($context->userId());
    }

    public function test_tenant_context_can_be_cleared(): void
    {
        $context = new TenantContext();
        $context->set(123, 456);
        $context->clear();

        $this->assertFalse($context->hasTenant());
    }

    public function test_tenant_context_snapshot_creation(): void
    {
        $context = new TenantContext();
        $context->set(123, 456);

        $snapshot = $context->snapshot();

        $this->assertSame(123, $snapshot->companyId);
        $this->assertSame(456, $snapshot->userId);
    }

    public function test_tenant_context_can_be_restored_from_snapshot(): void
    {
        $snapshot = new TenantContextSnapshot(789, 999);
        $context = new TenantContext();
        $context->restore($snapshot);

        $this->assertTrue($context->hasTenant());
        $this->assertSame(789, $context->companyId());
        $this->assertSame(999, $context->userId());
    }

    public function test_cross_tenant_isolation_context_switching(): void
    {
        $context = new TenantContext();

        // Set first tenant
        $context->set(123, 100);
        $this->assertSame(123, $context->companyId());

        // Switch to second tenant
        $context->set(456, 200);
        $this->assertSame(456, $context->companyId());
        $this->assertSame(200, $context->userId());

        // Verify we're in the second tenant
        $this->assertNotSame(123, $context->companyId());
    }

    public function test_multiple_context_instances_are_isolated(): void
    {
        $context1 = new TenantContext();
        $context2 = new TenantContext();

        $context1->set(123, 100);
        $context2->set(456, 200);

        // Verify isolation
        $this->assertSame(123, $context1->companyId());
        $this->assertSame(456, $context2->companyId());
        $this->assertSame(100, $context1->userId());
        $this->assertSame(200, $context2->userId());
    }
}
