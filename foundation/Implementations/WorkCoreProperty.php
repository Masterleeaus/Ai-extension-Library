<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCorePropertyContract;
use PDO;
use Foundation\Support\JsonHelper;

class WorkCoreProperty implements WorkCorePropertyContract
{
    private PDO $db;
    private string $tablePrefix = 'workcore_property_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function registerProperty(
        string $tenantId,
        array $propertyData
    ): string {
        $propertyId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}properties (id, tenant_id, data, registered_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $propertyId,
            $tenantId,
            json_encode($propertyData),
            date('c'),
        ]);

        return $propertyId;
    }

    public function getProperty(
        string $tenantId,
        string $propertyId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}properties WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$propertyId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = JsonHelper::decode($result['data']);
        }

        return $result ?: null;
    }

    public function updateProperty(
        string $tenantId,
        string $propertyId,
        array $updates
    ): bool {
        $property = $this->getProperty($tenantId, $propertyId);

        if (!$property) {
            return false;
        }

        $mergedData = array_merge($property['data'], $updates);

        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}properties SET data = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($mergedData), date('c'), $propertyId, $tenantId]);
    }

    public function registerAsset(
        string $tenantId,
        string $propertyId,
        array $assetData
    ): string {
        $assetId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}assets (id, tenant_id, property_id, data, registered_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $assetId,
            $tenantId,
            $propertyId,
            json_encode($assetData),
            date('c'),
        ]);

        return $assetId;
    }

    public function trackAssetMaintenance(
        string $tenantId,
        string $assetId,
        array $maintenanceData
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}asset_maintenance (asset_id, tenant_id, data, recorded_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $assetId,
            $tenantId,
            json_encode($maintenanceData),
            date('c'),
        ]);
    }

    public function storeDocument(
        string $tenantId,
        string $propertyId,
        array $documentData
    ): string {
        $documentId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}documents (id, tenant_id, property_id, data, stored_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $documentId,
            $tenantId,
            $propertyId,
            json_encode($documentData),
            date('c'),
        ]);

        return $documentId;
    }

    public function retrieveDocument(
        string $tenantId,
        string $documentId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}documents WHERE id = ? AND tenant_id = ?"
        );

        $stmt->execute([$documentId, $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['data'] = JsonHelper::decode($result['data']);
        }

        return $result ?: null;
    }

    public function getPropertyAudit(
        string $tenantId,
        string $propertyId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}property_audit WHERE tenant_id = ? AND property_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
