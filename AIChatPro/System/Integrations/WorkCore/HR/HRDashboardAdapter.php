<?php

namespace Extensions\AIChatPro\System\Integrations\WorkCore\HR;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;
use Illuminate\Support\Collection;

class HRDashboardAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getHRDashboard(string $tenantId, string $userId): array
    {
        return [
            'workforce' => $this->getWorkforceData($tenantId),
            'attendance' => $this->getAttendanceData($tenantId),
            'roster' => $this->getRosterData($tenantId),
            'compliance' => $this->getComplianceStatus($tenantId),
            'credentials' => $this->getCredentialStatus($tenantId),
            'ndis_compliance' => $this->getNDISCompliance($tenantId),
        ];
    }

    protected function getWorkforceData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/workforce', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getAttendanceData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/attendance', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getRosterData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/roster', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getComplianceStatus(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/compliance', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getCredentialStatus(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/credentials', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    protected function getNDISCompliance(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/ndis', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }
}
