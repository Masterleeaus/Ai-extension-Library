<?php

namespace Extensions\AIChatProFileChat\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreFileChatIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeFileChat(string $tenantId, string $userId): array
    {
        return [
            'files' => $this->getFiles($tenantId),
            'conversations' => $this->getConversations($tenantId),
            'extracted_data' => $this->getExtractedData($tenantId),
            'analysis' => $this->getAnalysis($tenantId),
            'documents' => $this->getDocuments($tenantId),
            'history' => $this->getHistory($tenantId),
        ];
    }

    public function getFiles(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/files', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getConversations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/conversations', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getExtractedData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/extracted_data', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getAnalysis(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/analysis', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getDocuments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/documents', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function getHistory(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('filechat/history', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    public function uploadFile(string $tenantId, array $fileData): array
    {
        $response = $this->workCoreGateway->action('filechat/upload_file', [
            'tenant_id' => $tenantId,
            'file_data' => $fileData,
        ]);
        return $response->data ?? [];
    }

    public function chatWithFile(string $tenantId, string $fileId, string $question): array
    {
        $response = $this->workCoreGateway->action('filechat/chat_with_file', [
            'tenant_id' => $tenantId,
            'file_id' => $fileId,
            'question' => $question,
        ]);
        return $response->data ?? [];
    }

    public function extractData(string $tenantId, string $fileId): array
    {
        $response = $this->workCoreGateway->action('filechat/extract_data', [
            'tenant_id' => $tenantId,
            'file_id' => $fileId,
        ]);
        return $response->data ?? [];
    }

    public function analyzeDocument(string $tenantId, string $fileId): array
    {
        $response = $this->workCoreGateway->action('filechat/analyze_document', [
            'tenant_id' => $tenantId,
            'file_id' => $fileId,
        ]);
        return $response->data ?? [];
    }

    public function deleteFile(string $tenantId, string $fileId): array
    {
        $response = $this->workCoreGateway->action('filechat/delete_file', [
            'tenant_id' => $tenantId,
            'file_id' => $fileId,
        ]);
        return $response->data ?? [];
    }
}
