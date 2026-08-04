<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\ExtensionLifecycleContract;
use PDO;

class ExtensionLifecycle implements ExtensionLifecycleContract
{
    private PDO $db;
    private string $tablePrefix = 'extension_lifecycle_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerExtension(
        string $tenantId,
        string $extensionName,
        array $extensionMetadata
    ): string {
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
            date('c'),
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
            $result['metadata'] = json_decode($result['metadata'], true);
        }

        return $result ?: null;
    }

    public function publishExtensionVersion(
        string $tenantId,
        string $extensionId,
        string $version,
        array $releaseNotes
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}versions (extension_id, tenant_id, version, release_notes, published_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $result = $stmt->execute([
            $extensionId,
            $tenantId,
            $version,
            json_encode($releaseNotes),
            date('c'),
        ]);

        if ($result) {
            $updateStmt = $this->db->prepare(
                "UPDATE {$this->tablePrefix}extensions SET status = ?, published_version = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
            );

            $updateStmt->execute(['published', $version, date('c'), $extensionId, $tenantId]);
        }

        return $result;
    }

    public function enableExtension(
        string $tenantId,
        string $extensionId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, enabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['enabled', date('c'), $extensionId, $tenantId]);
    }

    public function disableExtension(
        string $tenantId,
        string $extensionId,
        string $reason
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, disabled_reason = ?, disabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute(['disabled', $reason, date('c'), $extensionId, $tenantId]);
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
            date('c'),
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
