<?php

namespace Extensions\AIAgentWhatsappChannel\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreWhatsAppIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeWhatsAppOperations(string $tenantId, string $userId): array
    {
        return [
            'accounts' => $this->getAccounts($tenantId),
            'messages' => $this->getMessages($tenantId),
            'conversations' => $this->getConversations($tenantId),
            'groups' => $this->getGroups($tenantId),
            'media' => $this->getMedia($tenantId),
            'status' => $this->getStatus($tenantId),
        ];
    }

    public function getAccounts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_accounts', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getMessages(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_messages', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getConversations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_conversations', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getGroups(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_groups', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getMedia(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_media', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getStatus(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('messaging/whatsapp_status', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function sendTextMessage(string $tenantId, string $phoneNumber, string $message): array
    {
        $response = $this->workCoreGateway->action('messaging/send_whatsapp_text', [
            'tenant_id' => $tenantId,
            'phone_number' => $phoneNumber,
            'message' => $message,
        ]);
        return $response->data ?? [];
    }

    public function sendMediaMessage(string $tenantId, string $phoneNumber, array $mediaData): array
    {
        $response = $this->workCoreGateway->action('messaging/send_whatsapp_media', [
            'tenant_id' => $tenantId,
            'phone_number' => $phoneNumber,
            'media_data' => $mediaData,
        ]);
        return $response->data ?? [];
    }

    public function createGroup(string $tenantId, array $groupData): array
    {
        $response = $this->workCoreGateway->action('messaging/create_whatsapp_group', [
            'tenant_id' => $tenantId,
            'group_data' => $groupData,
        ]);
        return $response->data ?? [];
    }

    public function addGroupMember(string $tenantId, string $groupId, string $phoneNumber): array
    {
        $response = $this->workCoreGateway->action('messaging/add_group_member', [
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'phone_number' => $phoneNumber,
        ]);
        return $response->data ?? [];
    }

    public function removeGroupMember(string $tenantId, string $groupId, string $phoneNumber): array
    {
        $response = $this->workCoreGateway->action('messaging/remove_group_member', [
            'tenant_id' => $tenantId,
            'group_id' => $groupId,
            'phone_number' => $phoneNumber,
        ]);
        return $response->data ?? [];
    }

    public function broadcastMessage(string $tenantId, array $recipients, string $message): array
    {
        $response = $this->workCoreGateway->action('messaging/broadcast_message', [
            'tenant_id' => $tenantId,
            'recipients' => $recipients,
            'message' => $message,
        ]);
        return $response->data ?? [];
    }

    public function markAsRead(string $tenantId, string $messageId): array
    {
        $response = $this->workCoreGateway->action('messaging/mark_as_read', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
        ]);
        return $response->data ?? [];
    }

    public function searchMessages(string $tenantId, string $query): array
    {
        $response = $this->workCoreGateway->query('messaging/search_whatsapp_messages', [
            'tenant_id' => $tenantId,
            'query' => $query,
        ]);
        return $response->data ?? [];
    }
}
