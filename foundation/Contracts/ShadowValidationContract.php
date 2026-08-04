<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface ShadowValidationContract
{
    public function createValidation(
        string $tenantId,
        string $migrationId,
        string $dataSource,
        array $rules
    ): string;

    public function validateRecord(
        string $tenantId,
        string $validationId,
        array $sourceRecord,
        array $targetRecord
    ): array;

    public function getValidationStatus(
        string $tenantId,
        string $validationId
    ): ?array;

    public function compareResults(
        string $tenantId,
        string $validationId
    ): array;

    public function recordDiscrepancy(
        string $tenantId,
        string $validationId,
        array $discrepancy
    ): string;

    public function getDiscrepancies(
        string $tenantId,
        string $validationId
    ): array;

    public function markValidationComplete(
        string $tenantId,
        string $validationId,
        bool $approved
    ): bool;

    public function generateReport(
        string $tenantId,
        string $validationId
    ): string;
}
