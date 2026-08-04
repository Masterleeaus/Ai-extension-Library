<?php

namespace Tests\Feature\TenantContext;

use App\Domains\Shared\Context\TenantContextImpl;
use App\Domains\Shared\Authorization\DefaultAuthorizationPolicy;
use App\Domains\Shared\Authorization\ToolExecutionLimits;
use App\Domains\Shared\Authorization\ActionConstraints;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    private DefaultAuthorizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new DefaultAuthorizationPolicy();
    }

    public function test_authorization_policy_checks_permissions(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['tools:execute:read']
        );

        $this->assertTrue($this->policy->isAuthorized($context, 'tools:execute:read'));
        $this->assertFalse($this->policy->isAuthorized($context, 'admin:access'));
    }

    public function test_can_execute_tool_with_permission(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['tool:execute:send_email']
        );

        $this->assertTrue($this->policy->canExecuteTool($context, 'send_email'));
    }

    public function test_tool_execution_limits_default(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'service');

        $limits = $this->policy->getToolExecutionLimits($context, 'send_email');

        $this->assertEquals(1000, $limits->maxCallsPerHour);
        $this->assertEquals(10, $limits->maxConcurrentCalls);
        $this->assertEquals(300, $limits->maxTimeoutSeconds);
        $this->assertEquals(100.00, $limits->maxCostUsd);
    }

    public function test_tool_execution_limits_premium(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['tools:premium']
        );

        $limits = $this->policy->getToolExecutionLimits($context, 'send_email');

        $this->assertEquals(5000, $limits->maxCallsPerHour);
        $this->assertEquals(50, $limits->maxConcurrentCalls);
    }

    public function test_tool_execution_limits_enterprise(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['tools:enterprise']
        );

        $limits = $this->policy->getToolExecutionLimits($context, 'send_email');

        $this->assertEquals(100000, $limits->maxCallsPerHour);
        $this->assertEquals(500, $limits->maxConcurrentCalls);
        $this->assertEquals(3600, $limits->maxTimeoutSeconds);
        $this->assertEquals(5000.00, $limits->maxCostUsd);
    }

    public function test_workflow_action_requires_approval_for_sensitive_actions(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'service', [
            'workflow:action:create_invoice'
        ]);

        $constraints = $this->policy->getActionConstraints($context, 'create_invoice');

        $this->assertTrue($constraints->requiresApproval);
    }

    public function test_workflow_action_bypass_approval_with_permission(): void
    {
        $context = TenantContextImpl::forService('tenant-123', 'service', [
            'workflow:action:create_invoice',
            'workflows:bypass-approval'
        ]);

        $constraints = $this->policy->getActionConstraints($context, 'create_invoice');

        $this->assertFalse($constraints->requiresApproval);
    }

    public function test_connector_usage_authorization(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['connector:use:slack']
        );

        $this->assertTrue($this->policy->canUseConnector($context, 'slack'));
        $this->assertFalse($this->policy->canUseConnector($context, 'gmail'));
    }

    public function test_knowledge_ingestion_authorization(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['knowledge:ingest:pdf']
        );

        $this->assertTrue($this->policy->canIngestKnowledge($context, 'pdf'));
        $this->assertFalse($this->policy->canIngestKnowledge($context, 'webpage'));
    }

    public function test_authorization_reason_tracking(): void
    {
        $context = TenantContextImpl::forService(
            'tenant-123',
            'service',
            ['test:permission']
        );

        $this->policy->isAuthorized($context, 'test:permission');
        $this->assertStringContainsString('granted', $this->policy->getAuthorizationReason());

        $this->policy->isAuthorized($context, 'ungranted:permission');
        $this->assertStringContainsString('not granted', $this->policy->getAuthorizationReason());
    }
}
