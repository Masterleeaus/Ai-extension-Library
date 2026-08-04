<?php
namespace Extensions\ChatbotBooking\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreBookingIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializeBooking(string $tenantId, string $userId): array
    {
        return [
            'appointments' => $this->getAppointments($tenantId),
            'availability' => $this->getAvailability($tenantId),
            'bookings' => $this->getBookings($tenantId),
            'resources' => $this->getResources($tenantId),
            'reminders' => $this->getReminders($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getAppointments(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/appointments', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAvailability(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/availability', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getBookings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/bookings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getResources(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/resources', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getReminders(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/reminders', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('booking/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function createBooking(string $tenantId, array $bookingData): array
    {
        $response = $this->workCoreGateway->action('booking/create_booking', [
            'tenant_id' => $tenantId,
            'booking_data' => $bookingData,
        ]);
        return $response->data ?? [];
    }
}
