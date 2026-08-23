<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Exceptions\DatabaseException;
use Foundation\Support\DatabaseHelper;
use Foundation\Contracts\ExtensionLifecycleContract;
use Foundation\Support\InputValidator;
use Foundation\Support\ValidationException;
use Foundation\Support\TransactionHelper;
use PDO;
use Foundation\Support\JsonHelper;

class ExtensionLifecycle implements ExtensionLifecycleContract
{
    private DatabaseRepositoryContract $repository;
    private DatabaseHelper $helper;
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

        $stmt = $this->helper->safePrepare(
            "INSERT INTO {$this->tablePrefix}extensions (id, tenant_id, name, metadata, status, registered_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        try {
            $this->helper->safeExecute($stmt, [
                        $extensionId,
                        $tenantId,
                        $extensionName,
                        json_encode($extensionMetadata, 'EXECUTE'),
                        'draft',
                        date('c'),
                    ]);
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }

        return $extensionId;
    }

    public function getExtensionStatus(
        string $tenantId,
        string $extensionId
    ): ?array {
        $stmt = $this->helper->safePrepare(
            "SELECT * FROM {$this->tablePrefix}extensions WHERE id = ? AND tenant_id = ?"
        );

        try {
            $this->helper->safeExecute($stmt, [$extensionId, $tenantId], 'EXECUTE');
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
        $result = $this->repository->fetch($stmt, PDO::FETCH_ASSOC);

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
                    date('c'),
                ])) {
                    throw new \Exception('Failed to insert version record');
                }

                // Step 2: Update extension status
                $updateStmt = $db->prepare(
                    "UPDATE {$this->tablePrefix}extensions SET status = ?, published_version = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
                );

                if (!$updateStmt->execute(['published', $version, date('c'), $extensionId, $tenantId])) {
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
        $stmt = $this->helper->safePrepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, enabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        try {
            return $this->helper->safeExecute($stmt, ['enabled', date('c', 'EXECUTE'), $extensionId, $tenantId]);
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function disableExtension(
        string $tenantId,
        string $extensionId,
        string $reason
    ): bool {
        $stmt = $this->helper->safePrepare(
            "UPDATE {$this->tablePrefix}extensions SET status = ?, disabled_reason = ?, disabled_at = ? WHERE id = ? AND tenant_id = ?"
        );

        try {
            return $this->helper->safeExecute($stmt, ['disabled', $reason, date('c', 'EXECUTE'), $extensionId, $tenantId]);
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function grantExtensionPass(
        string $tenantId,
        string $extensionId,
        string $passType,
        int $durationDays
    ): string {
        $passId = bin2hex(random_bytes(16));

        $expiresAt = date('c', strtotime("+{$durationDays} days"));

        $stmt = $this->helper->safePrepare(
            "INSERT INTO {$this->tablePrefix}passes (id, extension_id, tenant_id, type, expires_at, granted_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        try {
            $this->helper->safeExecute($stmt, [
                        $passId,
                        $extensionId,
                        $tenantId,
                        $passType,
                        $expiresAt,
                        date('c', 'EXECUTE'),
                    ]);
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }

        return $passId;
    }

    public function validateExtensionPass(
        string $tenantId,
        string $passId
    ): bool {
        $stmt = $this->helper->safePrepare(
            "SELECT expires_at FROM {$this->tablePrefix}passes WHERE id = ? AND tenant_id = ?"
        );

        try {
            $this->helper->safeExecute($stmt, [$passId, $tenantId], 'EXECUTE');
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
        $result = $this->repository->fetch($stmt, PDO::FETCH_ASSOC);

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

        $stmt = $this->helper->safePrepare($query);
        try {
            $this->helper->safeExecute($stmt, $params, 'EXECUTE');
        } catch (DatabaseException $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }

        return $this->repository->fetchAll($stmt, PDO::FETCH_ASSOC);
    }
}
