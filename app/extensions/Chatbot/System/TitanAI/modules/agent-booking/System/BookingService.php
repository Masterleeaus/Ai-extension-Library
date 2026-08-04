<?php

declare(strict_types=1);

namespace App\Extensions\AgentBooking\System;

use App\Extensions\TitanAIGovernance\System\Booking\WorkCoreBookingCoordinator;
use DateTime;
use InvalidArgumentException;

final class BookingService
{
    public function __construct(private ?WorkCoreBookingCoordinator $coordinator = null) {}

    public function createBooking(array $data): array
    {
        return $this->coordinator()->book($this->normalise($data), $this->context($data));
    }

    public function getAvailableSlots(string $serviceType, DateTime $date, int $durationMinutes = 60): array
    {
        return $this->coordinator()->availability([
            'service_type' => $serviceType,
            'from' => $date->format(DATE_ATOM),
            'to' => (clone $date)->modify('+1 day')->format(DATE_ATOM),
            'duration_minutes' => $durationMinutes,
        ], $this->context([]));
    }

    public function updateBooking(string $bookingId, array $updates): array
    {
        if (isset($updates['scheduled_at'])) {
            return $this->coordinator()->reschedule($bookingId, $updates, $this->context($updates));
        }
        if (! app()->bound('WorkCoreAccessService')) {
            throw new InvalidArgumentException('WorkCore is unavailable. Booking changes were not applied.');
        }
        return app('WorkCoreAccessService')->executeFunction('jobs', 'update_job', [
            'job_id' => $bookingId,
            'updates' => $updates,
            '_governed' => true,
        ]);
    }

    public function rescheduleBooking(string $bookingId, DateTime $newTime): array
    {
        return $this->coordinator()->reschedule($bookingId, ['scheduled_at' => $newTime->format(DATE_ATOM)], $this->context([]));
    }

    public function cancelBooking(string $bookingId, ?string $reason = null): array
    {
        return $this->coordinator()->cancel($bookingId, $reason, $this->context([]));
    }

    public function sendConfirmation(string $bookingId): array
    {
        return app('WorkCoreAccessService')->executeFunction('communications', 'send_booking_confirmation', [
            'job_id' => $bookingId,
            '_governed' => true,
        ]);
    }

    public function sendReminder(string $bookingId, int $minutesBefore = 60): array
    {
        return app('WorkCoreAccessService')->executeFunction('communications', 'schedule_job_reminder', [
            'job_id' => $bookingId,
            'minutes_before' => $minutesBefore,
            '_governed' => true,
        ]);
    }

    public function getBookingHistory(string $customerId, int $limit = 20): array
    {
        return app('WorkCoreAccessService')->executeFunction('jobs', 'customer_job_history', [
            'customer_id' => $customerId,
            'limit' => max(1, min($limit, 100)),
            '_mutation' => false,
        ]);
    }

    private function normalise(array $data): array
    {
        return [
            'customer' => $data['customer'] ?? [
                'id' => $data['customer_id'] ?? null,
                'name' => $data['customer_name'] ?? null,
                'phone' => $data['customer_phone'] ?? null,
                'email' => $data['customer_email'] ?? null,
            ],
            'service_type' => $data['service_type'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'scheduled_until' => $data['scheduled_until'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? $data['duration'] ?? 60,
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'worker_id' => $data['worker_id'] ?? null,
            'source' => 'chatbot_agent_booking',
        ];
    }

    private function context(array $data): array
    {
        return [
            'tenant_id' => (string) ($data['tenant_id'] ?? auth()->user()?->company_id ?? ''),
            'user_id' => (string) ($data['user_id'] ?? auth()->id() ?? ''),
            'device_id' => (string) ($data['device_id'] ?? request()->header('X-Device-ID', 'server')),
            'idempotency_key' => $data['idempotency_key'] ?? request()->header('Idempotency-Key'),
        ];
    }

    private function coordinator(): WorkCoreBookingCoordinator
    {
        return $this->coordinator ??= app(WorkCoreBookingCoordinator::class);
    }
}
