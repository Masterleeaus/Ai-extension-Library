<?php

namespace Extensions\AIChatProFolders\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreFoldersIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeFolders(string $tenantId, string $userId): array
    {
        return [
            'folders' => $this->getFolders($tenantId),
            'conversations' => $this->getConversations($tenantId),
            'organization' => $this->getOrganization($tenantId),
            'permissions' => $this->getPermissions($tenantId),
            'sharing' => $this->getSharing($tenantId),
            'tags' => $this->getTags($tenantId),
        ];
    }

    public function getFolders(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/folders', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getConversations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/conversations', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getOrganization(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/organization', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getPermissions(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/permissions', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getSharing(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/sharing', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getTags(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('folders/tags', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function createFolder(string $tenantId, array $folderData): array
    {
        $response = $this->workCoreGateway->action('folders/create_folder', [
            'tenant_id' => $tenantId,
            'folder_data' => $folderData,
        ]);
        return $response->data ?? [];
    }

    public function moveConversation(string $tenantId, string $conversationId, string $folderId): array
    {
        $response = $this->workCoreGateway->action('folders/move_conversation', [
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'folder_id' => $folderId,
        ]);
        return $response->data ?? [];
    }

    public function shareFolder(string $tenantId, string $folderId, array $userIds): array
    {
        $response = $this->workCoreGateway->action('folders/share_folder', [
            'tenant_id' => $tenantId,
            'folder_id' => $folderId,
            'user_ids' => $userIds,
        ]);
        return $response->data ?? [];
    }

    public function tagConversation(string $tenantId, string $conversationId, array $tags): array
    {
        $response = $this->workCoreGateway->action('folders/tag_conversation', [
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'tags' => $tags,
        ]);
        return $response->data ?? [];
    }

    public function deleteFolder(string $tenantId, string $folderId): array
    {
        $response = $this->workCoreGateway->action('folders/delete_folder', [
            'tenant_id' => $tenantId,
            'folder_id' => $folderId,
        ]);
        return $response->data ?? [];
    }
}
