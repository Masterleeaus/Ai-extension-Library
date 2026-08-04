<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface BookingMigrationContract
{
    public function mapLegacyBooking(
        string $tenantId,
        array $legacyBooking
    ): array;

    public function validateMappedBooking(
        string $tenantId,
        array $mappedBooking
    ): array;

    public function migrateBooking(
        string $tenantId,
        string $bookingId,
        array $legacyData
    ): string;

    public function getMigrationStatus(
        string $tenantId,
        string $bookingId
    ): ?array;

    public function rollbackBookingMigration(
        string $tenantId,
        string $bookingId
    ): bool;

    public function getBulkMigrationProgress(
        string $tenantId,
        string $migrationBatchId
    ): array;

    public function startBulkMigration(
        string $tenantId,
        array $legacyBookings
    ): string;

    public function validateMigrationComplete(
        string $tenantId,
        string $migrationBatchId
    ): bool;
}
