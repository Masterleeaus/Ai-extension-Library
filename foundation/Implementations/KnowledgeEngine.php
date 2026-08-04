<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\KnowledgeEngineContract;
use PDO;

class KnowledgeEngine implements KnowledgeEngineContract
{
    private PDO $db;
    private string $tablePrefix = 'knowledge_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function ingest(
        string $tenantId,
        string $documentId,
        string $content,
        array $metadata = []
    ): string {
        $ingestionId = bin2hex(random_bytes(16));
        $createdAt = date('c');

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}ingestions (id, tenant_id, document_id, content, metadata, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $ingestionId,
            $tenantId,
            $documentId,
            $content,
            json_encode($metadata),
            $createdAt,
        ]);

        return $ingestionId;
    }

    public function retrieve(
        string $tenantId,
        string $documentId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}documents WHERE tenant_id = ? AND id = ? LIMIT 1"
        );
        $stmt->execute([$tenantId, $documentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function delete(
        string $tenantId,
        string $documentId
    ): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->tablePrefix}documents WHERE tenant_id = ? AND id = ?"
        );
        return $stmt->execute([$tenantId, $documentId]);
    }

    public function search(
        string $tenantId,
        string $query,
        ?int $limit = null
    ): array {
        $limit = $limit ?? 10;
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}documents
             WHERE tenant_id = ? AND (content LIKE ? OR metadata LIKE ?)
             LIMIT ?"
        );

        $searchPattern = "%{$query}%";
        $stmt->execute([$tenantId, $searchPattern, $searchPattern, $limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cite(
        string $tenantId,
        string $documentId,
        int $startOffset,
        int $endOffset
    ): string {
        $citation = [
            'document_id' => $documentId,
            'start' => $startOffset,
            'end' => $endOffset,
            'created_at' => date('c'),
        ];

        return base64_encode(json_encode($citation));
    }

    public function getContext(
        string $tenantId,
        string $documentId,
        int $contextWindow = 500
    ): ?array {
        $doc = $this->retrieve($tenantId, $documentId);
        if (!$doc) {
            return null;
        }

        return [
            'document_id' => $doc['id'],
            'content' => substr($doc['content'], 0, $contextWindow),
            'metadata' => json_decode($doc['metadata'], true),
        ];
    }

    public function storeConversation(
        string $tenantId,
        string $conversationId,
        array $messages
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}conversations (tenant_id, id, messages, created_at)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE messages = ?"
        );

        $messagesJson = json_encode($messages);
        return $stmt->execute([
            $tenantId,
            $conversationId,
            $messagesJson,
            date('c'),
            $messagesJson,
        ]);
    }
}
