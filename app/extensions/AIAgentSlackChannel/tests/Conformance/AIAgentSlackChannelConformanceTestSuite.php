<?php

namespace Extensions\AIAgentSlackChannel\Tests\Conformance;

use PHPUnit\Framework\TestCase;
use Extensions\AIAgentSlackChannel\System\Integrations\WorkCoreSlackIntegrationService;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class AIAgentSlackChannelConformanceTestSuite extends TestCase
{
    protected $mockGateway;
    protected $service;

    protected function setUp(): void
    {
        $this->mockGateway = $this->createMock(WorkCoreGateway::class);
        $this->service = new WorkCoreSlackIntegrationService($this->mockGateway);
    }

    public function testInitializeSlackOperations()
    {
        $tenantId = 'tenant_123';
        $userId = 'user_456';

        $this->mockGateway->method('query')->willReturn((object)['data' => []]);

        $result = $this->service->initializeSlackOperations($tenantId, $userId);

        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('conversations', $result);
        $this->assertArrayHasKey('apps', $result);
        $this->assertArrayHasKey('workflows', $result);
    }

    public function testGetChannels()
    {
        $tenantId = 'tenant_123';
        $channelsData = [
            ['id' => 'C123', 'name' => 'general'],
            ['id' => 'C456', 'name' => 'random'],
        ];

        $this->mockGateway->method('query')
            ->with('collaboration/slack_channels', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $channelsData]);

        $result = $this->service->getChannels($tenantId);

        $this->assertEquals($channelsData, $result);
    }

    public function testGetUsers()
    {
        $tenantId = 'tenant_123';
        $usersData = [
            ['id' => 'U123', 'name' => 'john'],
            ['id' => 'U456', 'name' => 'jane'],
        ];

        $this->mockGateway->method('query')
            ->with('collaboration/slack_users', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $usersData]);

        $result = $this->service->getUsers($tenantId);

        $this->assertEquals($usersData, $result);
    }

    public function testGetMessages()
    {
        $tenantId = 'tenant_123';
        $messagesData = [
            ['ts' => '1234567890.123456', 'text' => 'Hello', 'user' => 'U123'],
            ['ts' => '1234567891.123456', 'text' => 'Hi', 'user' => 'U456'],
        ];

        $this->mockGateway->method('query')
            ->with('collaboration/slack_messages', ['tenant_id' => $tenantId])
            ->willReturn((object)['data' => $messagesData]);

        $result = $this->service->getMessages($tenantId);

        $this->assertEquals($messagesData, $result);
    }

    public function testSendMessage()
    {
        $tenantId = 'tenant_123';
        $messageData = [
            'channel' => 'C123',
            'text' => 'Automated message',
        ];

        $this->mockGateway->method('action')
            ->with('collaboration/send_slack_message', [
                'tenant_id' => $tenantId,
                'message_data' => $messageData,
            ])
            ->willReturn((object)['data' => ['ts' => '1234567890.123456']]);

        $result = $this->service->sendMessage($tenantId, $messageData);

        $this->assertArrayHasKey('ts', $result);
    }

    public function testPostToChannel()
    {
        $tenantId = 'tenant_123';
        $channelId = 'C123';
        $message = 'Test message';

        $this->mockGateway->method('action')
            ->with('collaboration/post_to_channel', [
                'tenant_id' => $tenantId,
                'channel_id' => $channelId,
                'message' => $message,
                'metadata' => [],
            ])
            ->willReturn((object)['data' => ['posted' => true]]);

        $result = $this->service->postToChannel($tenantId, $channelId, $message);

        $this->assertTrue($result['posted']);
    }

    public function testSendDirectMessage()
    {
        $tenantId = 'tenant_123';
        $userId = 'U123';
        $message = 'Direct message';

        $this->mockGateway->method('action')
            ->with('collaboration/send_dm', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'message' => $message,
            ])
            ->willReturn((object)['data' => ['sent' => true]]);

        $result = $this->service->sendDirectMessage($tenantId, $userId, $message);

        $this->assertTrue($result['sent']);
    }

    public function testUpdateMessage()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';
        $updatedText = 'Updated message';

        $this->mockGateway->method('action')
            ->with('collaboration/update_slack_message', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
                'text' => $updatedText,
            ])
            ->willReturn((object)['data' => ['updated' => true]]);

        $result = $this->service->updateMessage($tenantId, $messageId, $updatedText);

        $this->assertTrue($result['updated']);
    }

    public function testDeleteMessage()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';

        $this->mockGateway->method('action')
            ->with('collaboration/delete_slack_message', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
            ])
            ->willReturn((object)['data' => ['deleted' => true]]);

        $result = $this->service->deleteMessage($tenantId, $messageId);

        $this->assertTrue($result['deleted']);
    }

    public function testAddReaction()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';
        $emoji = 'thumbsup';

        $this->mockGateway->method('action')
            ->with('collaboration/add_reaction', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
                'emoji' => $emoji,
            ])
            ->willReturn((object)['data' => ['reaction_added' => true]]);

        $result = $this->service->addReaction($tenantId, $messageId, $emoji);

        $this->assertTrue($result['reaction_added']);
    }

    public function testCreateThread()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';
        $threadMessage = 'Thread reply';

        $this->mockGateway->method('action')
            ->with('collaboration/create_thread', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
                'thread_message' => $threadMessage,
            ])
            ->willReturn((object)['data' => ['thread_ts' => '1234567890.123456']]);

        $result = $this->service->createThread($tenantId, $messageId, $threadMessage);

        $this->assertArrayHasKey('thread_ts', $result);
    }

    public function testGetThreadReplies()
    {
        $tenantId = 'tenant_123';
        $messageId = 'msg_123';
        $repliesData = [
            ['ts' => '1234567890.123456', 'text' => 'Reply 1'],
            ['ts' => '1234567891.123456', 'text' => 'Reply 2'],
        ];

        $this->mockGateway->method('query')
            ->with('collaboration/thread_replies', [
                'tenant_id' => $tenantId,
                'message_id' => $messageId,
            ])
            ->willReturn((object)['data' => $repliesData]);

        $result = $this->service->getThreadReplies($tenantId, $messageId);

        $this->assertEquals($repliesData, $result);
    }

    public function testInviteUserToChannel()
    {
        $tenantId = 'tenant_123';
        $channelId = 'C123';
        $userId = 'U456';

        $this->mockGateway->method('action')
            ->with('collaboration/invite_to_channel', [
                'tenant_id' => $tenantId,
                'channel_id' => $channelId,
                'user_id' => $userId,
            ])
            ->willReturn((object)['data' => ['invited' => true]]);

        $result = $this->service->inviteUserToChannel($tenantId, $channelId, $userId);

        $this->assertTrue($result['invited']);
    }

    public function testSearchMessages()
    {
        $tenantId = 'tenant_123';
        $query = 'important';

        $this->mockGateway->method('query')
            ->with('collaboration/search_slack_messages', [
                'tenant_id' => $tenantId,
                'query' => $query,
            ])
            ->willReturn((object)['data' => [
                ['ts' => '1234567890.123456', 'text' => 'Important message'],
            ]]);

        $result = $this->service->searchMessages($tenantId, $query);

        $this->assertCount(1, $result);
    }

    public function testTenantIsolationInSlackOperations()
    {
        $tenantId = 'tenant_123';

        $this->mockGateway->expects($this->atLeastOnce())
            ->method('query')
            ->will($this->returnCallback(function ($endpoint, $params) {
                $this->assertEquals($params['tenant_id'], 'tenant_123');
                return (object)['data' => []];
            }));

        $this->service->getChannels($tenantId);
    }

    public function testNullDataHandling()
    {
        $tenantId = 'tenant_123';

        $this->mockGateway->method('query')
            ->willReturn((object)['data' => null]);

        $result = $this->service->getChannels($tenantId);

        $this->assertEquals([], $result);
    }
}
