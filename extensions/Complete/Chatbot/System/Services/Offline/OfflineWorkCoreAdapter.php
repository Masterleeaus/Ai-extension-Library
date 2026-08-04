<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;

class OfflineWorkCoreAdapter
{
    protected LocalBrain $localBrain;
    protected WorkCoreSyncService $syncService;
    protected const CONFIDENCE_THRESHOLD = 0.8;
    protected const QUEUE_TABLE = 'workcore_offline_queue';

    public function __construct(LocalBrain $localBrain, WorkCoreSyncService $syncService)
    {
        $this->localBrain = $localBrain;
        $this->syncService = $syncService;
    }

    public function executeActionOffline(
        string $actionType,
        array $payload,
        array $context = []
    ): array {
        $supportedActions = [
            'create_contact', 'create_invoice', 'schedule_job',
            'assign_staff', 'update_property'
        ];

        if (!in_array($actionType, $supportedActions)) {
            return [
                'success' => false,
                'error' => "Action '{$actionType}' not supported offline",
                'cloud_used' => false,
            ];
        }

        $tenantId = $context['tenant_id'] ?? (auth()->check() ? auth()->user()->tenant_id : null);
        $userId = $context['user_id'] ?? (auth()->check() ? auth()->id() : null);

        if (!$tenantId || !$userId) {
            return [
                'success' => false,
                'error' => 'Tenant and user context required',
                'cloud_used' => false,
            ];
        }

        // Use LocalBrain to validate action
        $processResult = $this->localBrain->process(
            input: "Execute WorkCore action: $actionType",
            context: array_merge($context, ['action' => $actionType])
        );

        $confidence = $processResult['confidence'] ?? 0.0;

        if ($confidence < self::CONFIDENCE_THRESHOLD) {
            $this->queueForSync($tenantId, $userId, $actionType, $payload, 'awaiting-approval');

            return [
                'success' => false,
                'queued_for_approval' => true,
                'confidence' => $confidence,
                'reason' => 'Low confidence action',
                'cloud_used' => false,
            ];
        }

        // Execute action
        try {
            $result = match ($actionType) {
                'create_contact' => $this->createContactOffline($tenantId, $payload),
                'create_invoice' => $this->createInvoiceOffline($tenantId, $payload),
                'schedule_job' => $this->scheduleJobOffline($tenantId, $payload),
                'assign_staff' => $this->assignStaffOffline($tenantId, $payload),
                'update_property' => $this->updatePropertyOffline($tenantId, $payload),
                default => ['success' => false, 'error' => 'Unknown action'],
            };

            if ($result['success'] ?? false) {
                $this->queueForSync($tenantId, $userId, $actionType, $payload, 'executed');
            }

            return array_merge($result, ['cloud_used' => false]);
        } catch (\Exception $e) {
            $this->queueForSync($tenantId, $userId, $actionType, $payload, 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'queued_for_retry' => true,
                'cloud_used' => false,
            ];
        }
    }

    protected function createContactOffline(string $tenantId, array $payload): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_contacts')) {
                return ['success' => false, 'error' => 'Contact table not available'];
            }

            DB::table('workcore_contacts')->insert([
                'tenant_id' => $tenantId,
                'name' => $payload['name'] ?? '',
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'type' => $payload['type'] ?? 'individual',
                'metadata' => json_encode($payload['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'action' => 'create_contact'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createInvoiceOffline(string $tenantId, array $payload): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_invoices')) {
                return ['success' => false, 'error' => 'Invoice table not available'];
            }

            $invoiceId = DB::table('workcore_invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => $payload['invoice_number'] ?? 'INV-' . time(),
                'customer_id' => $payload['customer_id'] ?? null,
                'amount' => $payload['amount'] ?? 0,
                'status' => 'draft',
                'due_date' => $payload['due_date'] ?? null,
                'items' => json_encode($payload['items'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'action' => 'create_invoice', 'invoice_id' => $invoiceId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function scheduleJobOffline(string $tenantId, array $payload): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_jobs')) {
                return ['success' => false, 'error' => 'Jobs table not available'];
            }

            $jobId = DB::table('workcore_jobs')->insertGetId([
                'tenant_id' => $tenantId,
                'title' => $payload['title'] ?? '',
                'description' => $payload['description'] ?? null,
                'scheduled_date' => $payload['scheduled_date'] ?? now(),
                'priority' => $payload['priority'] ?? 'medium',
                'status' => 'scheduled',
                'metadata' => json_encode($payload['metadata'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return ['success' => true, 'action' => 'schedule_job', 'job_id' => $jobId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function assignStaffOffline(string $tenantId, array $payload): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_assignments')) {
                return ['success' => false, 'error' => 'Assignments table not available'];
            }

            $availableStaff = $this->getAvailableStaffOffline($tenantId, $payload['skills'] ?? []);

            if (empty($availableStaff)) {
                return ['success' => false, 'error' => 'No available staff'];
            }

            $assignmentId = DB::table('workcore_assignments')->insertGetId([
                'tenant_id' => $tenantId,
                'staff_id' => $availableStaff[0]['id'],
                'job_id' => $payload['job_id'] ?? null,
                'assigned_at' => now(),
                'status' => 'assigned',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'action' => 'assign_staff',
                'assignment_id' => $assignmentId,
                'staff_id' => $availableStaff[0]['id'],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function updatePropertyOffline(string $tenantId, array $payload): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_properties')) {
                return ['success' => false, 'error' => 'Properties table not available'];
            }

            DB::table('workcore_properties')
                ->where('tenant_id', $tenantId)
                ->where('id', $payload['property_id'])
                ->update([
                    'name' => $payload['name'] ?? null,
                    'description' => $payload['description'] ?? null,
                    'location' => json_encode($payload['location'] ?? []),
                    'metadata' => json_encode($payload['metadata'] ?? []),
                    'updated_at' => now(),
                ]);

            return ['success' => true, 'action' => 'update_property'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getAvailableStaffOffline(string $tenantId, array $requiredSkills = []): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_staff')) {
                return [];
            }

            $query = DB::table('workcore_staff')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active');

            $results = $query->get(['id', 'name', 'skills', 'availability_score'])->toArray();

            if (!empty($requiredSkills)) {
                $filtered = [];
                foreach ($results as $staff) {
                    $staffSkills = is_string($staff->skills) ? json_decode($staff->skills, true) : $staff->skills;
                    if (is_array($staffSkills) && !empty(array_intersect($requiredSkills, $staffSkills))) {
                        $filtered[] = $staff;
                    }
                }
                return array_slice($filtered, 0, 5);
            }

            return array_slice($results, 0, 5);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function queueForSync(
        string $tenantId,
        string $userId,
        string $actionType,
        array $payload,
        string $status,
        ?string $error = null
    ): void {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::QUEUE_TABLE)) {
                return;
            }

            DB::table(self::QUEUE_TABLE)->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'action_type' => $actionType,
                'payload' => json_encode($payload),
                'status' => $status,
                'error_message' => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
