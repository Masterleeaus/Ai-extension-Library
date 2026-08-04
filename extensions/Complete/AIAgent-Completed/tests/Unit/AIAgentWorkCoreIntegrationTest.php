<?php

namespace Tests\Extensions\AIAgent\Unit;

use App\Extensions\AIAgent\System\WorkCore\AIAgentWorkCoreIntegration;
use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\WorkflowAuthorizationPolicyContract;
use PHPUnit\Framework\TestCase;

class AIAgentWorkCoreIntegrationTest extends TestCase
{
    private AIAgentWorkCoreIntegration $integration;
    private TenantContextContract $tenantContext;
    private OperationContextContract $operationContext;
    private WorkflowAuthorizationPolicyContract $workflowPolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->createMock(TenantContextContract::class);
        $this->operationContext = $this->createMock(OperationContextContract::class);
        $this->workflowPolicy = $this->createMock(WorkflowAuthorizationPolicyContract::class);

        $this->integration = new AIAgentWorkCoreIntegration(
            $this->tenantContext,
            $this->operationContext,
            $this->workflowPolicy
        );
    }

    public function testResolveWorkflowContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('actorSubject')->willReturn('user');
        $this->operationContext->method('correlationId')->willReturn('corr-123');

        $context = $this->integration->resolveWorkflowContext('process_order');

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertEquals(100, $context['actorId']);
        $this->assertTrue($context['autonomous']);
        $this->assertEquals('process_order', $context['workflowName']);
    }

    public function testIsGovernedAction(): void
    {
        $this->assertTrue($this->integration->isGovernedAction('delete'));
        $this->assertTrue($this->integration->isGovernedAction('charge_customer'));
        $this->assertFalse($this->integration->isGovernedAction('read'));
    }

    public function testRequiresApproval(): void
    {
        $this->assertTrue($this->integration->requiresApproval('charge_customer'));
        $this->assertTrue($this->integration->requiresApproval('refund'));
        $this->assertFalse($this->integration->requiresApproval('read'));
    }

    public function testGetRateLimitBudget(): void
    {
        $apiLimits = $this->integration->getRateLimitBudget('api_call');
        $this->assertEquals(100, $apiLimits['limit']);
        $this->assertEquals(3600, $apiLimits['window']);

        $notificationLimits = $this->integration->getRateLimitBudget('notification');
        $this->assertEquals(50, $notificationLimits['limit']);
    }

    public function testGetWorkCoreContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('workerId')->willReturn(200);
        $this->operationContext->method('branchId')->willReturn(5);
        $this->operationContext->method('territoryId')->willReturn(15);
        $this->operationContext->method('locale')->willReturn('en_US');
        $this->operationContext->method('timezone')->willReturn('America/New_York');
        $this->operationContext->method('correlationId')->willReturn('corr-123');
        $this->operationContext->method('causationId')->willReturn('cause-456');

        $context = $this->integration->getWorkCoreContext();

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertTrue($context['autonomous']);
        $this->assertEquals('en_US', $context['locale']);
    }
}
