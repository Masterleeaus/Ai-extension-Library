<?php

namespace Extensions\AIChatPro\System\Integrations\WorkCore\Operations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class OperationsDashboardAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getOperationsDashboard(string $tenantId, string $userId): array
    {
        return [
            'jobs' => $this->getJobs($tenantId),
            'scheduling' => $this->getScheduling($tenantId),
            'dispatch' => $this->getDispatch($tenantId),
            'fleet' => $this->getFleet($tenantId),
            'recurring_services' => $this->getRecurringServices($tenantId),
            'forms_and_inspections' => $this->getFormsAndInspections($tenantId),
            'repairs' => $this->getRepairs($tenantId),
        ];
    }

    protected function getJobs(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/jobs', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getScheduling(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/scheduling', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getDispatch(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/dispatch', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getFleet(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/fleet', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getRecurringServices(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/recurring_services', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getFormsAndInspections(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/forms_inspections', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getRepairs(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('work_operations/repairs', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
