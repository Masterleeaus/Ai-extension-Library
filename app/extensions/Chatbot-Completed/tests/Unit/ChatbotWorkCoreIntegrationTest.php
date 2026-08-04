<?php

namespace Tests\Extensions\Chatbot\Unit;

use App\Extensions\Chatbot\System\WorkCore\ChatbotWorkCoreIntegration;
use App\Domains\WorkCore\System\Contracts\OperationContextContract;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use App\Domains\WorkCore\System\Authorization\Policies\ConnectorAuthorizationPolicyContract;
use PHPUnit\Framework\TestCase;

class ChatbotWorkCoreIntegrationTest extends TestCase
{
    private ChatbotWorkCoreIntegration $integration;
    private TenantContextContract $tenantContext;
    private OperationContextContract $operationContext;
    private ConnectorAuthorizationPolicyContract $connectorPolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = $this->createMock(TenantContextContract::class);
        $this->operationContext = $this->createMock(OperationContextContract::class);
        $this->connectorPolicy = $this->createMock(ConnectorAuthorizationPolicyContract::class);

        $this->integration = new ChatbotWorkCoreIntegration(
            $this->tenantContext,
            $this->operationContext,
            $this->connectorPolicy
        );
    }

    public function testResolveConversationContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('correlationId')->willReturn('corr-123');
        $this->operationContext->method('locale')->willReturn('en_US');

        $context = $this->integration->resolveConversationContext(999);

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertEquals(100, $context['actorId']);
        $this->assertEquals(999, $context['conversationId']);
        $this->assertEquals('en_US', $context['locale']);
    }

    public function testCanAccessConversation(): void
    {
        $this->operationContext->method('hasContext')->willReturn(true);
        $this->operationContext->method('actorId')->willReturn(100);

        $this->assertTrue($this->integration->canAccessConversation(999));
    }

    public function testCanUseConnector(): void
    {
        $this->connectorPolicy->method('canUseConnector')
            ->with('twilio', $this->operationContext, ['account_id' => 'xxx'])
            ->willReturn(true);

        $this->assertTrue($this->integration->canUseConnector('twilio', ['account_id' => 'xxx']));
    }

    public function testCanIngestKnowledge(): void
    {
        $this->operationContext->method('hasContext')->willReturn(true);

        $this->assertTrue($this->integration->canIngestKnowledge('documents'));
    }

    public function testGetDenialReasonForConnector(): void
    {
        $this->connectorPolicy->method('getDenialReasonForConnector')
            ->with('twilio', $this->operationContext)
            ->willReturn('Connector disabled');

        $reason = $this->integration->getDenialReasonForConnector('twilio');
        $this->assertEquals('Connector disabled', $reason);
    }

    public function testCanGoOffline(): void
    {
        $this->assertTrue($this->integration->canGoOffline());
    }

    public function testGetWorkCoreContext(): void
    {
        $this->tenantContext->method('companyId')->willReturn(1);
        $this->tenantContext->method('userId')->willReturn(10);
        $this->operationContext->method('actorId')->willReturn(100);
        $this->operationContext->method('locale')->willReturn('en_US');
        $this->operationContext->method('timezone')->willReturn('UTC');
        $this->operationContext->method('correlationId')->willReturn('corr-123');

        $context = $this->integration->getWorkCoreContext();

        $this->assertEquals(1, $context['tenantId']);
        $this->assertEquals(10, $context['userId']);
        $this->assertTrue($context['offlineCapable']);
        $this->assertEquals('en_US', $context['locale']);
    }
}
