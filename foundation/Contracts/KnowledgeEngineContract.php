<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface KnowledgeEngineContract
{
    public function ingest(
        string $tenantId,
        string $sourceId,
        string $content,
        array $metadata = []
    ): string;

    public function retrieve(
        string $tenantId,
        string $query,
        int $limit = 10
    ): array;

    public function delete(string $tenantId, string $knowledgeId): bool;

    public function search(
        string $tenantId,
        string $query,
        array $filters = []
    ): array;

    public function cite(string $knowledgeId): array;

    public function getContext(string $tenantId, string $conversationId): array;

    public function storeConversation(
        string $tenantId,
        string $conversationId,
        array $messages,
        array $metadata = []
    ): bool;
}
