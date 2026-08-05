<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\BookingMigrationContract;
use PDO;
use Foundation\Support\JsonHelper;
use Foundation\Support\DateTimeHelper;

class BookingMigration implements BookingMigrationContract
{
    private PDO $db;
    private string $tablePrefix = 'booking_migration_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function mapLegacyBooking(
        string $tenantId,
        array $legacyBooking
    ): array {
        return [
            'tenant_id' => $tenantId,
            'original_id' => $legacyBooking['id'] ?? null,
            'service_id' => $legacyBooking['service_id'] ?? null,
            'customer_id' => $legacyBooking['customer_id'] ?? $legacyBooking['user_id'] ?? null,
            'scheduled_at' => $legacyBooking['date'] ?? $legacyBooking['scheduled_at'] ?? null,
            'duration_minutes' => $legacyBooking['duration'] ?? $legacyBooking['duration_minutes'] ?? 60,
            'status' => $this->mapStatus($legacyBooking['status'] ?? 'pending'),
            'notes' => $legacyBooking['notes'] ?? null,
            'metadata' => [
                'legacy_format' => true,
                'original_data' => $legacyBooking,
            ],
        ];
    }

    public function validateMappedBooking(
        string $tenantId,
        array $mappedBooking
    ): array {
        $errors = [];

        if (empty($mappedBooking['customer_id'])) {
            $errors[] = 'Customer ID is required';
        }

        if (empty($mappedBooking['service_id'])) {
            $errors[] = 'Service ID is required';
        }

        if (empty($mappedBooking['scheduled_at'])) {
            $errors[] = 'Scheduled date/time is required';
        }

        if (!in_array($mappedBooking['status'] ?? '', ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $errors[] = 'Invalid booking status';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function migrateBooking(
        string $tenantId,
        string $bookingId,
        array $legacyData
    ): string {
        $mapped = $this->mapLegacyBooking($tenantId, $legacyData);
        $validation = $this->validateMappedBooking($tenantId, $mapped);

        if (!$validation['valid']) {
            throw new \RuntimeException('Booking validation failed: ' . implode(', ', $validation['errors']));
        }

        $migrationId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}records (id, tenant_id, legacy_booking_id, mapped_data, status, migrated_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $migrationId,
            $tenantId,
            $bookingId,
            json_encode($mapped),
            'completed',
            DateTimeHelper::now(),
        ]);

        return $migrationId;
    }

    public function getMigrationStatus(
        string $tenantId,
        string $bookingId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}records WHERE tenant_id = ? AND legacy_booking_id = ?"
        );

        $stmt->execute([$tenantId, $bookingId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            $record['mapped_data'] = JsonHelper::decode($record['mapped_data']);
        }

        return $record ?: null;
    }

    public function rollbackBookingMigration(
        string $tenantId,
        string $bookingId
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}records SET status = ?, rolled_back_at = ? WHERE tenant_id = ? AND legacy_booking_id = ?"
        );

        return $stmt->execute(['rolled_back', DateTimeHelper::now(), $tenantId, $bookingId]);
    }

    public function getBulkMigrationProgress(
        string $tenantId,
        string $migrationBatchId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
             FROM {$this->tablePrefix}records WHERE tenant_id = ? AND batch_id = ?"
        );

        $stmt->execute([$tenantId, $migrationBatchId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => $stats['total'] ?? 0,
            'completed' => $stats['completed'] ?? 0,
            'pending' => ($stats['total'] ?? 0) - ($stats['completed'] ?? 0),
            'progress_percentage' => $stats['total'] > 0 ? (($stats['completed'] ?? 0) / $stats['total']) * 100 : 0,
        ];
    }

    public function startBulkMigration(
        string $tenantId,
        array $legacyBookings
    ): string {
        $batchId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}batches (id, tenant_id, total_bookings, status, started_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $batchId,
            $tenantId,
            count($legacyBookings),
            'processing',
            DateTimeHelper::now(),
        ]);

        return $batchId;
    }

    public function validateMigrationComplete(
        string $tenantId,
        string $migrationBatchId
    ): bool {
        $progress = $this->getBulkMigrationProgress($tenantId, $migrationBatchId);

        return $progress['pending'] === 0;
    }

    private function mapStatus(string $legacyStatus): string {
        $mapping = [
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'complete' => 'completed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ];

        return $mapping[strtolower($legacyStatus)] ?? 'pending';
    }
}
