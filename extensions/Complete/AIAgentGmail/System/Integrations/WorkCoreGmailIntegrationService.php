<?php

namespace Extensions\AIAgentGmail\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreGmailIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeGmailOperations(string $tenantId, string $userId): array
    {
        return [
            'email_accounts' => $this->getEmailAccounts($tenantId),
            'messages' => $this->getMessages($tenantId),
            'drafts' => $this->getDrafts($tenantId),
            'labels' => $this->getLabels($tenantId),
            'attachments' => $this->getAttachments($tenantId),
            'threads' => $this->getThreads($tenantId),
        ];
    }

    public function getEmailAccounts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/email_accounts', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getMessages(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/messages', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getDrafts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/drafts', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getLabels(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/labels', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getAttachments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/attachments', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getThreads(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('communication/threads', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function sendEmail(string $tenantId, array $emailData): array
    {
        $response = $this->workCoreGateway->action('communication/send_email', [
            'tenant_id' => $tenantId,
            'email_data' => $emailData,
        ]);
        return $response->data ?? [];
    }

    public function archiveEmail(string $tenantId, string $messageId): array
    {
        $response = $this->workCoreGateway->action('communication/archive_email', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
        ]);
        return $response->data ?? [];
    }

    public function createDraft(string $tenantId, array $draftData): array
    {
        $response = $this->workCoreGateway->action('communication/create_draft', [
            'tenant_id' => $tenantId,
            'draft_data' => $draftData,
        ]);
        return $response->data ?? [];
    }

    public function labelEmail(string $tenantId, string $messageId, array $labels): array
    {
        $response = $this->workCoreGateway->action('communication/label_email', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
            'labels' => $labels,
        ]);
        return $response->data ?? [];
    }

    public function searchEmails(string $tenantId, string $query): array
    {
        $response = $this->workCoreGateway->query('communication/search_emails', [
            'tenant_id' => $tenantId,
            'query' => $query,
        ]);
        return $response->data ?? [];
    }

    public function markEmailAsRead(string $tenantId, string $messageId): array
    {
        $response = $this->workCoreGateway->action('communication/mark_as_read', [
            'tenant_id' => $tenantId,
            'message_id' => $messageId,
        ]);
        return $response->data ?? [];
    }
}
