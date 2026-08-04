<?php

namespace Tests\Extensions\AIChatPro\Unit;

use App\Extensions\AIChatPro\System\WorkCore\AiChatProWorkCoreIntegration;
use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\ToolAuthorizationPolicyContract;
use PHPUnit\Framework\TestCase;

class AiChatProWorkCoreIntegrationTest extends TestCase
{
    private AiChatProWorkCoreIntegration $integration;
    private TenantContextContract $tenantContext;
    private OperationContextContract $operationContext;
    private ToolAuthorizationPolicyContract $toolPolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->createMock(TenantContextContract::class);
        $this->operationContext = $this->createMock(OperationContextContract::class);
        $this->toolPolicy = $this->createMock(ToolAuthorizationPolicyContract::class);

        $this->integration = new AiChatProWorkCoreIntegration(
            $this->tenantContext,
            $this->operationContext,
            $this->toolPolicy
        );
    }

    public function testResolveConversationContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('correlationId')->willReturn('corr-123');

        $context = $this->integration->resolveConversationContext(999);

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertEquals(100, $context['actorId']);
        $this->assertEquals(999, $context['conversationId']);
    }

    public function testCanAccessConversation(): void
    {
        $this->operationContext->method('hasContext')->willReturn(true);
        $this->operationContext->method('actorId')->willReturn(100);

        $this->assertTrue($this->integration->canAccessConversation(999));
    }

    public function testCanAccessConversationWithoutContext(): void
    {
        $this->operationContext->method('hasContext')->willReturn(false);

        $this->assertFalse($this->integration->canAccessConversation(999));
    }

    public function testCanExecuteSkill(): void
    {
        $this->toolPolicy->method('canExecuteTool')
            ->with('summarize', $this->operationContext, ['text' => '...'])
            ->willReturn(true);

        $this->assertTrue($this->integration->canExecuteSkill('summarize', ['text' => '...']));
    }

    public function testCanAccessFileChat(): void
    {
        $this->operationContext->method('hasContext')->willReturn(true);

        $this->assertTrue($this->integration->canAccessFileChat(123));
    }

    public function testGetDenialReasonForSkill(): void
    {
        $this->toolPolicy->method('getDenialReason')
            ->with('summarize', $this->operationContext)
            ->willReturn('Permission denied');

        $reason = $this->integration->getDenialReasonForSkill('summarize');
        $this->assertEquals('Permission denied', $reason);
    }

    public function testGetWorkCoreContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('workerId')->willReturn(200);
        $this->operationContext->method('branchId')->willReturn(5);
        $this->operationContext->method('locale')->willReturn('en_US');
        $this->operationContext->method('timezone')->willReturn('UTC');
        $this->operationContext->method('correlationId')->willReturn('corr-123');
        $this->operationContext->method('causationId')->willReturn('cause-456');

        $context = $this->integration->getWorkCoreContext();

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertEquals(100, $context['actorId']);
        $this->assertEquals('en_US', $context['locale']);
    }
}
