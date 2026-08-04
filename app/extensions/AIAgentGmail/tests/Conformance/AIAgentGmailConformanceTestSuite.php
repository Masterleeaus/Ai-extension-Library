<?php

namespace Extensions\AIAgentGmail\Tests\Conformance;

use PHPUnit\Framework\TestCase;
use Extensions\AIAgentGmail\System\Integrations\WorkCoreGmailIntegrationService;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class AIAgentGmailConformanceTestSuite extends TestCase
{
    protected $mockGateway;
    protected $service;

    protected function setUp(): void
    {
        $this->mockGateway = $this->createMock(WorkCoreGateway::class);
        $this->service = new WorkCoreGmailIntegrationService($this->mockGateway);
    }

    public function testInitializeGmailOperations()
    {
        $tenantId = 'tenant_123';
        $userId = 'user_456';

        $this->mockGateway->method('query')->willReturn((object)['data' => []]);

        $result = $this->service->initializeGmailOperations($tenantId, $userId);

        $this->assertArrayHasKey('email_accounts', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('drafts', $result);
        $this->assertArrayHasKey('labels', $result);
        $this->assertArrayHasKey('attachments', $result);
        $this->assertArrayHasKey('threads', $result);
    }

    public function testGetEmailAccounts()
    {
        $tenantId = 'tenant_123';
        $accountsData = [
            ['id' => 'acc_1', 'email' => 'user@example.com'],
            ['id' => 'acc_2', 'email' => 'alt@example.com'],
        ];

        $this->mockGateway->method('query')
            ->with('communication/email_accounts', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $accountsData]);

        $result = $this->service->getEmailAccounts($tenantId);

        $this->assertEquals($accountsData, $result);
    }

    public function testGetMessages()
    {
        $tenantId = 'tenant_123';
        $messagesData = [
            ['id' => 'msg_1', 'subject' => 'Test 1', 'from' => 'sender@example.com'],
            ['id' => 'msg_2', 'subject' => 'Test 2', 'from' => 'other@example.com'],
        ];

        $this->mockGateway->method('query')
            ->with('communication/messages', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $messagesData]);

        $result = $this->service->getMessages($tenantId);

        $this->assertEquals($messagesData, $result);
    }

    public function testGetDrafts()
    {
        $tenantId = 'tenant_123';
        $draftsData = [
            ['id' => 'draft_1', 'to' => 'recipient@example.com', 'subject' => 'Draft'],
        ];

        $this->mockGateway->method('query')
            ->with('communication/drafts', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $draftsData]);

        $result = $this->service->getDrafts($tenantId);

        $this->assertEquals($draftsData, $result);
    }

    public function testGetLabels()
    {
        $tenantId = 'tenant_123';
        $labelsData = [
            ['id' => 'label_1', 'name' => 'Important'],
            ['id' => 'label_2', 'name' => 'Work'],
        ];

        $this->mockGateway->method('query')
            ->with('communication/labels', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $labelsData]);

        $result = $this->service->getLabels($tenantId);

        $this->assertEquals($labelsData, $result);
    }

    public function testGetAttachments()
    {
        $tenantId = 'tenant_123';
        $attachmentsData = [
            ['id' => 'att_1', 'filename' => 'document.pdf', 'size' => 102400],
        ];

        $this->mockGateway->method('query')
            ->with('communication/attachments', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $attachmentsData]);

        $result = $this->service->getAttachments($tenantId);

        $this->assertEquals($attachmentsData, $result);
    }

    public function testGetThreads()
    {
        $tenantId = 'tenant_123';
        $threadsData = [
            ['id' => 'thread_1', 'subject' => 'Conversation', 'message_count' => 3],
        ];

        $this->mockGateway->method('query')
            ->with('communication/threads', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $threadsData]);

        $result = $this->service->getThreads($tenantId);

        $this->assertEquals($threadsData, $result);
    }

    public function testSendEmail()
    {
        $tenantId = 'tenant_123';
        $emailData = [
            'to' => 'recipient@example.com',
            'subject' => 'Test Email',
            'body' => 'This is a test',
        ];

        $this->mockGateway->method('action')
            ->with('communication/send_email', [
                'tenant_id' => $tenantId,
                'email_data' => $emailData,
            ])
            ->willReturn((object)['data' => ['message_id' => 'msg_new_1']]);

        $result = $this->service->sendEmail($tenantId, $emailData);

        $this->assertArrayHasKey('message_id', $result);
    }

    public function testArchiveEmail()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';

        $this->mockGateway->method('action')
            ->with('communication/archive_email', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
            ])
            ->willReturn((object)['data' => ['archived' => true]]);

        $result = $this->service->archiveEmail($tenantId, $messageId);

        $this->assertTrue($result['archived']);
    }

    public function testCreateDraft()
    {
        $tenantId = 'tenant_123';
        $draftData = [
            'to' => 'recipient@example.com',
            'subject' => 'New Draft',
            'body' => 'Draft content',
        ];

        $this->mockGateway->method('action')
            ->with('communication/create_draft', [
                'tenant_id' => $tenantId,
                'draft_data' => $draftData,
            ])
            ->willReturn((object)['data' => ['draft_id' => 'draft_new_1']]);

        $result = $this->service->createDraft($tenantId, $draftData);

        $this->assertArrayHasKey('draft_id', $result);
    }

    public function testLabelEmail()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';
        $labels = ['Important', 'Work'];

        $this->mockGateway->method('action')
            ->with('communication/label_email', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
                'labels' => $labels,
            ])
            ->willReturn((object)['data' => ['labeled' => true]]);

        $result = $this->service->labelEmail($tenantId, $messageId, $labels);

        $this->assertTrue($result['labeled']);
    }

    public function testSearchEmails()
    {
        $tenantId = 'tenant_123';
        $query = 'important emails';

        $this->mockGateway->method('query')
            ->with('communication/search_emails', [
                'tenant_id' => $tenantId,
                'query' => $query,
            ])
            ->willReturn((object)['data' => [
                ['id' => 'msg_1', 'subject' => 'Important'],
                ['id' => 'msg_2', 'subject' => 'Also Important'],
            ]]);

        $result = $this->service->searchEmails($tenantId, $query);

        $this->assertCount(2, $result);
    }

    public function testMarkEmailAsRead()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';

        $this->mockGateway->method('action')
            ->with('communication/mark_as_read', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
            ])
            ->willReturn((object)['data' => ['marked_as_read' => true]]);

        $result = $this->service->markEmailAsRead($tenantId, $messageId);

        $this->assertTrue($result['marked_as_read']);
    }

    public function testTenantIsolationInEmailOperations()
    {
        $tenantId1 = 'tenant_123';
        $tenantId2 = 'tenant_456';

        $this->mockGateway->expects($this->atLeastOnce())
            ->method('query')
            ->will($this->returnCallback(function ($endpoint, $params) {
                $this->assertEquals($params['tenant_id'], 'tenant_123');
                return (object)['data' => []];
            }));

        $this->service->getMessages($tenantId1);
    }

    public function testNullDataHandling()
    {
        $tenantId = 'tenant_123';

        $this->mockGateway->method('query')
            ->willReturn((object)['data' => null]);

        $result = $this->service->getMessages($tenantId);

        $this->assertEquals([], $result);
    }
}
