<?php

declare(strict_types=1);

namespace Foundation\Implementations;

use Foundation\Contracts\BookingEngineContract;
use PDO;

class BookingEngine implements BookingEngineContract
{
    private PDO $db;
    private string $tablePrefix = 'bookings_';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createBooking(
        string $tenantId,
        array $booking
    ): string {
        $bookingId = bin2hex(random_bytes(16));

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->tablePrefix}bookings (id, tenant_id, data, status, created_at)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $bookingId,
            $tenantId,
            json_encode($booking),
            'pending',
            date('c'),
        ]);

        return $bookingId;
    }

    public function getBooking(
        string $tenantId,
        string $bookingId
    ): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}bookings WHERE id = ? AND tenant_id = ?"
        );
        $stmt->execute([$bookingId, $tenantId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['data'] = json_decode($result['data'], true);
        }

        return $result ?: null;
    }

    public function updateBooking(
        string $tenantId,
        string $bookingId,
        array $updates
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}bookings SET data = ?, updated_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            json_encode($updates),
            date('c'),
            $bookingId,
            $tenantId,
        ]);
    }

    public function cancelBooking(
        string $tenantId,
        string $bookingId,
        string $reason = ''
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->tablePrefix}bookings
             SET status = ?, cancellation_reason = ?, cancelled_at = ?
             WHERE id = ? AND tenant_id = ?"
        );

        return $stmt->execute([
            'cancelled',
            $reason,
            date('c'),
            $bookingId,
            $tenantId,
        ]);
    }

    public function listBookings(
        string $tenantId,
        array $filters = []
    ): array {
        $query = "SELECT * FROM {$this->tablePrefix}bookings WHERE tenant_id = ?";
        $params = [$tenantId];

        if (isset($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = $filters['limit'];
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkAvailability(
        string $tenantId,
        string $resourceId,
        int $startTime,
        int $endTime
    ): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->tablePrefix}bookings
             WHERE tenant_id = ? AND resource_id = ? AND status = 'confirmed'
             AND start_time < ? AND end_time > ?"
        );

        $stmt->execute([$tenantId, $resourceId, $endTime, $startTime]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] == 0;
    }

    public function getProviderAdapters(
        string $tenantId
    ): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->tablePrefix}providers WHERE tenant_id = ?"
        );
        $stmt->execute([$tenantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
