<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\WorkCorePropertyContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class WorkCoreProperty implements WorkCorePropertyContract
{
    private PDO $db;
    private const TABLE_PREFIX = 'workcore_property_';
    private const TABLE_ASSET_MAINTENANCE = self::TABLE_PREFIX . 'asset_maintenance';
    private const TABLE_ASSETS = self::TABLE_PREFIX . 'assets';
    private const TABLE_DOCUMENTS = self::TABLE_PREFIX . 'documents';
    private const TABLE_PROPERTIES = self::TABLE_PREFIX . 'properties';
    private const TABLE_PROPERTY_AUDIT = self::TABLE_PREFIX . 'property_audit';
    private string $tablePrefix = self::TABLE_PREFIX;

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
            "INSERT INTO " . self::TABLE_PROPERTIES . " (id, tenant_id, data, registered_at)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $propertyId,
            $tenantId,
            json_encode($propertyData),
            DateTimeHelper::now(),
        ]);

        return $propertyId;
    }

    public function getProperty(
        string $tenantId,
        string $propertyId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_PROPERTIES . " WHERE id = ? AND tenant_id = ?"
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
            "UPDATE " . self::TABLE_PROPERTIES . " SET data = ?, updated_at = ? WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([json_encode($mergedData), DateTimeHelper::now(), $propertyId, $tenantId]);
    }

    public function registerAsset(
        string $tenantId,
        string $propertyId,
        array $assetData
    ): string {
        $assetId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_ASSETS . " (id, tenant_id, property_id, data, registered_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $assetId,
            $tenantId,
            $propertyId,
            json_encode($assetData),
            DateTimeHelper::now(),
        ]);

        return $assetId;
    }

    public function trackAssetMaintenance(
        string $tenantId,
        string $assetId,
        array $maintenanceData
    ): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_ASSET_MAINTENANCE . " (asset_id, tenant_id, data, recorded_at)
             VALUES (?, ?, ?, ?)"
        );

        return $stmt->execute([
            $assetId,
            $tenantId,
            json_encode($maintenanceData),
            DateTimeHelper::now(),
        ]);
    }

    public function storeDocument(
        string $tenantId,
        string $propertyId,
        array $documentData
    ): string {
        $documentId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO " . self::TABLE_DOCUMENTS . " (id, tenant_id, property_id, data, stored_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $documentId,
            $tenantId,
            $propertyId,
            json_encode($documentData),
            DateTimeHelper::now(),
        ]);

        return $documentId;
    }

    public function retrieveDocument(
        string $tenantId,
        string $documentId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM " . self::TABLE_DOCUMENTS . " WHERE id = ? AND tenant_id = ?"
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
            "SELECT * FROM " . self::TABLE_PROPERTY_AUDIT . " WHERE tenant_id = ? AND property_id = ? ORDER BY created_at DESC"
        );

        $stmt->execute([$tenantId, $propertyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
