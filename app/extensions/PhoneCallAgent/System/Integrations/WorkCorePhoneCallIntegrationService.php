<?php
namespace Extensions\PhoneCallAgent\System\Integrations;
use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCorePhoneCallIntegrationService
{
    protected $workCoreGateway;
    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }
    public function initializePhoneCall(string $tenantId, string $userId): array
    {
        return [
            'calls' => $this->getCalls($tenantId),
            'voicemails' => $this->getVoicemails($tenantId),
            'recordings' => $this->getRecordings($tenantId),
            'contacts' => $this->getContacts($tenantId),
            'automation' => $this->getAutomation($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }
    public function getCalls(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/calls', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getVoicemails(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/voicemails', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getRecordings(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/recordings', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getContacts(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/contacts', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAutomation(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/automation', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('phone/analytics', ['tenant_id' => $tenantId]);
        return $response->data ?? [];
    }
    public function makeCall(string $tenantId, string $phoneNumber): array
    {
        $response = $this->workCoreGateway->action('phone/make_call', [
            'tenant_id' => $tenantId,
            'phone_number' => $phoneNumber,
        ]);
        return $response->data ?? [];
    }
    public function recordCall(string $tenantId, string $callId): array
    {
        $response = $this->workCoreGateway->action('phone/record_call', [
            'tenant_id' => $tenantId,
            'call_id' => $callId,
        ]);
        return $response->data ?? [];
    }
}
