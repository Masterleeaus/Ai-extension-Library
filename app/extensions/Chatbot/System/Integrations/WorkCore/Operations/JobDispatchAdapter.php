<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\Operations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class JobDispatchAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getJobContext(string $tenantId, array $conversationContext): array
    {
        return [
            'job_booking' => $this->getJobBooking($tenantId),
            'job_status' => $this->getJobStatus($tenantId, $conversationContext),
            'dispatch_updates' => $this->getDispatchUpdates($tenantId),
            'fleet_locations' => $this->getFleetLocations($tenantId),
            'recurring_services' => $this->getRecurringServices($tenantId),
            'forms' => $this->getFormData($tenantId),
            'repairs_tracking' => $this->getRepairsTracking($tenantId),
        ];
    }

    protected function getJobBooking(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/jobs', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getJobStatus(string $tenantId, array $context): array
    {
        $response = $this->workCoreGateway->query('work_operations/job_status', [
            'tenant_id' => $tenantId,
            'job_id' => $context['job_id'] ?? null,
        ]);
        return $response->data ?? [];
    }

    protected function getDispatchUpdates(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/dispatch', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getFleetLocations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/fleet', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getRecurringServices(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/recurring', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getFormData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/forms', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getRepairsTracking(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/repairs', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
}
