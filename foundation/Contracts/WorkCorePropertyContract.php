<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface WorkCorePropertyContract
{
    public function registerProperty(
        string $tenantId,
        array $propertyData
    ): string;

    public function getProperty(
        string $tenantId,
        string $propertyId
    ): ?array;

    public function updateProperty(
        string $tenantId,
        string $propertyId,
        array $updates
    ): bool;

    public function registerAsset(
        string $tenantId,
        string $propertyId,
        array $assetData
    ): string;

    public function trackAssetMaintenance(
        string $tenantId,
        string $assetId,
        array $maintenanceData
    ): bool;

    public function storeDocument(
        string $tenantId,
        string $propertyId,
        array $documentData
    ): string;

    public function retrieveDocument(
        string $tenantId,
        string $documentId
    ): ?array;

    public function getPropertyAudit(
        string $tenantId,
        string $propertyId
    ): array;
}
