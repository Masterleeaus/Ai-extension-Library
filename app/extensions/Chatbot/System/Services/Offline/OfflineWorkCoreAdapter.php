<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use App\Extensions\Chatbot\System\InteractionEngine\LocalBrain\LocalBrainFacade;
use Illuminate\Support\Facades\DB;

class OfflineWorkCoreAdapter
{
    protected LocalBrainFacade $localBrain;
    protected const CONFIDENCE_THRESHOLD = 0.8;
    protected const QUEUE_TABLE = 'workcore_offline_queue';

    public function __construct(LocalBrainFacade $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    public function executeActionOffline(
        string $actionType,
        array $payload,
        array $context = []
    ): array {
        // Validate action is supported offline
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

        $tenantId = $context['tenant_id'] ?? auth()->user()->tenant_id;
        $userId = $context['user_id'] ?? auth()->id();

        // Use LocalBrain to validate and reason about the action
        $decision = $this->localBrain->makeDecision(
            action: $actionType,
            context: $context,
            payload: $payload
        );

        if ($decision['confidence'] < self::CONFIDENCE_THRESHOLD) {
            // Queue for approval when online
            $this->queueForSync($tenantId, $userId, $actionType, $payload, 'awaiting-approval');

            return [
                'success' => false,
                'queued_for_approval' => true,
                'confidence' => $decision['confidence'],
                'reason' => $decision['reasoning'] ?? 'Low confidence decision',
                'cloud_used' => false,
            ];
        }

        // Execute the action
        try {
            $result = match ($actionType) {
                'create_contact' => $this->createContactOffline($tenantId, $payload),
                'create_invoice' => $this->createInvoiceOffline($tenantId, $payload),
                'schedule_job' => $this->scheduleJobOffline($tenantId, $payload),
                'assign_staff' => $this->assignStaffOffline($tenantId, $payload),
                'update_property' => $this->updatePropertyOffline($tenantId, $payload),
                default => ['success' => false, 'error' => 'Unknown action'],
            };

            if ($result['success']) {
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
    }

    protected function createInvoiceOffline(string $tenantId, array $payload): array
    {
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
    }

    protected function scheduleJobOffline(string $tenantId, array $payload): array
    {
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
    }

    protected function assignStaffOffline(string $tenantId, array $payload): array
    {
        // Use LocalBrain skill matching for optimal staff assignment
        $availableStaff = $this->getAvailableStaffOffline($tenantId, $payload['skills'] ?? []);

        if (empty($availableStaff)) {
            return [
                'success' => false,
                'error' => 'No available staff matching required skills',
            ];
        }

        $assignedStaff = $availableStaff[0]; // Use LocalBrain's top recommendation

        $assignmentId = DB::table('workcore_assignments')->insertGetId([
            'tenant_id' => $tenantId,
            'staff_id' => $assignedStaff['id'],
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
            'staff_id' => $assignedStaff['id'],
        ];
    }

    protected function updatePropertyOffline(string $tenantId, array $payload): array
    {
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
    }

    protected function getAvailableStaffOffline(string $tenantId, array $requiredSkills = []): array
    {
        $query = DB::table('workcore_staff')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active');

        if (!empty($requiredSkills)) {
            $query->whereRaw("JSON_CONTAINS(skills, JSON_ARRAY(?))", [implode(',', $requiredSkills)]);
        }

        return $query->orderByDesc('availability_score')
            ->limit(5)
            ->get(['id', 'name', 'skills', 'availability_score'])
            ->toArray();
    }

    protected function queueForSync(
        string $tenantId,
        string $userId,
        string $actionType,
        array $payload,
        string $status,
        ?string $error = null
    ): void {
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
    }

    public function syncQueuedOperations(string $tenantId): array
    {
        $queued = DB::table(self::QUEUE_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'synced')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($queued as $operation) {
            try {
                // Sync to cloud when online
                // For now, mark as synced
                DB::table(self::QUEUE_TABLE)
                    ->where('id', $operation->id)
                    ->update(['status' => 'synced', 'updated_at' => now()]);
                $synced++;
            } catch (\Exception $e) {
                DB::table(self::QUEUE_TABLE)
                    ->where('id', $operation->id)
                    ->update(['error_message' => $e->getMessage()]);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
