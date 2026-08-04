<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface BookingEngineContract
{
    public function createBooking(
        string $tenantId,
        array $booking
    ): string;

    public function getBooking(string $tenantId, string $bookingId): ?array;

    public function updateBooking(
        string $tenantId,
        string $bookingId,
        array $updates
    ): bool;

    public function cancelBooking(
        string $tenantId,
        string $bookingId,
        string $reason = ''
    ): bool;

    public function listBookings(
        string $tenantId,
        array $filters = []
    ): array;

    public function checkAvailability(
        string $tenantId,
        string $resourceId,
        int $startTime,
        int $endTime
    ): bool;

    public function getProviderAdapters(string $tenantId): array;
}
