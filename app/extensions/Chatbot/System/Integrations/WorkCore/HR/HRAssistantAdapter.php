<?php

namespace Extensions\Chatbot\System\Integrations\WorkCore\HR;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class HRAssistantAdapter
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function getHRContext(string $tenantId, array $conversationContext): array
    {
        return [
            'staff_roster' => $this->getStaffRoster($tenantId),
            'attendance' => $this->getAttendanceInfo($tenantId),
            'shift_management' => $this->getShiftManagement($tenantId),
            'compliance_status' => $this->getComplianceStatus($tenantId),
            'credentials' => $this->getCredentials($tenantId),
            'hr_policies' => $this->getHRPolicies($tenantId),
        ];
    }

    protected function getStaffRoster(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/roster', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getAttendanceInfo(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/attendance', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getShiftManagement(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/shifts', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getComplianceStatus(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/compliance', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getCredentials(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/credentials', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }

    protected function getHRPolicies(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('workforce_assurance/policies', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
}
