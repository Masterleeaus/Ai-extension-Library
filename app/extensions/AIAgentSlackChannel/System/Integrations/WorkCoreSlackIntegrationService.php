<?php

namespace Extensions\AIAgentSlackChannel\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreSlackIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeSlackOperations(string $tenantId, string $userId): array
    {
        return [
            'channels' => $this->getChannels($tenantId),
            'users' => $this->getUsers($tenantId),
            'messages' => $this->getMessages($tenantId),
            'conversations' => $this->getConversations($tenantId),
            'apps' => $this->getInstalledApps($tenantId),
            'workflows' => $this->getWorkflows($tenantId),
        ];
    }

    public function getChannels(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_channels', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getUsers(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_users', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getMessages(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_messages', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getConversations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_conversations', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getInstalledApps(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_apps', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getWorkflows(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('collaboration/slack_workflows', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function sendMessage(string $tenantId, array $messageData): array
    {
        $response = $this->workCoreGateway->action('collaboration/send_slack_message', [
            'tenant_id' => $tenantId,
            'message_data' => $messageData,
        ]);
        return $response->data ?? [];
    }

    public function postToChannel(string $tenantId, string $channelId, string $message, array $metadata = []): array
    {
        $response = $this->workCoreGateway->action('collaboration/post_to_channel', [
            'tenant_id' => $tenantId,
            'channel_id' => $channelId,
            'message' => $message,
            'metadata' => $metadata,
        ]);
        return $response->data ?? [];
    }

    public function sendDirectMessage(string $tenantId, string $userId, string $message): array
    {
        $response = $this->workCoreGateway->action('collaboration/send_dm', [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'message' => $message,
        ]);
        return $response->data ?? [];
    }

    public function updateMessage(string $tenantId, string $messageId, string $updatedText): array
    {
        $response = $this->workCoreGateway->action('collaboration/update_slack_message', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
            'text' => $updatedText,
        ]);
        return $response->data ?? [];
    }

    public function deleteMessage(string $tenantId, string $messageId): array
    {
        $response = $this->workCoreGateway->action('collaboration/delete_slack_message', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
        ]);
        return $response->data ?? [];
    }

    public function addReaction(string $tenantId, string $messageId, string $emoji): array
    {
        $response = $this->workCoreGateway->action('collaboration/add_reaction', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
            'emoji' => $emoji,
        ]);
        return $response->data ?? [];
    }

    public function createThread(string $tenantId, string $messageId, string $threadMessage): array
    {
        $response = $this->workCoreGateway->action('collaboration/create_thread', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
            'thread_message' => $threadMessage,
        ]);
        return $response->data ?? [];
    }

    public function getThreadReplies(string $tenantId, string $messageId): array
    {
        $response = $this->workCoreGateway->query('collaboration/thread_replies', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
        ]);
        return $response->data ?? [];
    }

    public function inviteUserToChannel(string $tenantId, string $channelId, string $userId): array
    {
        $response = $this->workCoreGateway->action('collaboration/invite_to_channel', [
            'tenant_id' => $tenantId,
            'channel_id' => $channelId,
            'user_id' => $userId,
        ]);
        return $response->data ?? [];
    }

    public function searchMessages(string $tenantId, string $query): array
    {
        $response = $this->workCoreGateway->query('collaboration/search_slack_messages', [
            'tenant_id' => $tenantId,
            'query' => $query,
        ]);
        return $response->data ?? [];
    }
}
