<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ExtensionLifecycleContract;
use Foundation\Support\InputValidator;
use Foundation\Support\ValidationException;
use Foundation\Support\TransactionHelper;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class ExtensionLifecycle implements ExtensionLifecycleContract
{
    private PDO $db;
    private string $tablePrefix = 'extension_lifecycle_';
    private TransactionHelper $transactions;

    public function __construct(PDO $db, ?TransactionHelper $transactions = null)
    {
        $this->db = $db;
        $this->transactions = $transactions ?? new TransactionHelper($db);
    }

    public function registerExtension(
        string $tenantId,
        string $extensionName,
        array $extensionMetadata
    ): string {
        // Validate inputs
        InputValidator::validateTenantId($tenantId);
        InputValidator::validateNonEmptyString($extensionName, 'extensionName', 255);
        InputValidator::validateArray($extensionMetadata, 'extensionMetadata', false, 1000);

        $extensionId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}extensions (id, tenant_id, name, metadata, status, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $extensionId,
            $tenantId,
            $extensionName,
            json_encode($extensionMetadata),
            'draft',
            DateTimeHelper::now(),
        ]);

        return $extensionId;
    }

    public function getExtensionStatus(
        string $tenantId,
        string $extensionId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}extensions WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$extensionId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['metadata'] = JsonHelper::decode($result['metadata']);
        }

        return $result ?: null;
    }

    public function publishExtensionVersion(
        string $tenantId,
        string $extensionId,
        string $version,
        array $releaseNotes
    ): bool {
        return $this->transactions->executeInTransaction(
            function (PDO $db) use ($tenantId, $extensionId, $version, $releaseNotes) {
                // Step 1: Insert version record
                $stmt = $db->prepare(
                    "INSERT INTO {$this->tablePrefix}versions (extension_id, tenant_id, version, release_notes, published_at)
                     VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmt->execute([
                    $extensionId,
                    $tenantId,
                    $version,
                    json_encode($releaseNotes),
                    DateTimeHelper::now(),
                ])) {
                    throw new \Exception('Failed to insert version record');
                }

                // Step 2: Update extension status
                $updateStmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}extensions SET status = ?, published_version = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$updateStmt->execute(['published', $version, DateTimeHelper::now(), $extensionId, $tenantId])) {
                    throw new \Exception('Failed to update extension status');
                }

                return true;
            },
            'publishExtensionVersion',
            ['extension_id' => $extensionId, 'version' => $version]
        );
    }

    public function enableExtension(
        string $tenantId,
        string $extensionId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, enabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['enabled', DateTimeHelper::now(), $extensionId, $tenantId]);
    }

    public function disableExtension(
        string $tenantId,
        string $extensionId,
        string $reason
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, disabled_reason = ?, disabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['disabled', $reason, DateTimeHelper::now(), $extensionId, $tenantId]);
    }

    public function grantExtensionPass(
        string $tenantId,
        string $extensionId,
        string $passType,
        int $durationDays
    ): string {
        $passId = bin2hex(random_bytes(16));

        $expiresAt = date('c', strtotime("+{$durationDays} days"));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}passes (id, extension_id, tenant_id, type, expires_at, granted_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $passId,
            $extensionId,
            $tenantId,
            $passType,
            $expiresAt,
            DateTimeHelper::now(),
        ]);

        return $passId;
    }

    public function validateExtensionPass(
        string $tenantId,
        string $passId
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT expires_at FROM {$this->tablePrefix}passes WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$passId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return false;
        }

        return strtotime($result['expires_at']) > time();
    }

    public function listExtensions(
        string $tenantId,
        ?string $status = null
    ): array {
        $query = "SELECT id, name, status, registered_at FROM {$this->tablePrefix}extensions WHERE tenant_id = ?";
        $params = [$tenantId];

        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY registered_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
