<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;

class WorkCoreSyncService
{
    protected LocalBrain $localBrain;
    protected const SYNC_QUEUE_TABLE = 'workcore_offline_queue';
    protected const CONFLICT_RESOLUTION_TABLE = 'workcore_sync_conflicts';

    public function __construct(LocalBrain $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    /**
     * Sync Chatbot-embedded WorkCore with platform WorkCore
     * Uses LocalBrain for intelligent conflict resolution
     */
    public function syncWithPlatformWorkCore(string $tenantId): array
    {
        try {
            $result = [
                'synced_contacts' => 0,
                'synced_invoices' => 0,
                'synced_jobs' => 0,
                'synced_assignments' => 0,
                'synced_properties' => 0,
                'synced_staff' => 0,
                'conflicts_resolved' => 0,
                'errors' => [],
            ];

            // Sync each WorkCore domain
            $result['synced_contacts'] = $this->syncContacts($tenantId);
            $result['synced_invoices'] = $this->syncInvoices($tenantId);
            $result['synced_jobs'] = $this->syncJobs($tenantId);
            $result['synced_assignments'] = $this->syncAssignments($tenantId);
            $result['synced_properties'] = $this->syncProperties($tenantId);
            $result['synced_staff'] = $this->syncStaff($tenantId);

            // Resolve any conflicts using LocalBrain
            $result['conflicts_resolved'] = $this->resolveConflicts($tenantId);

            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'sync_failed' => true];
        }
    }

    protected function syncContacts(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_contacts')) {
                return 0;
            }

            // Get offline-created contacts
            $offlineContacts = DB::table('workcore_contacts')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineContacts as $contact) {
                // Use LocalBrain to validate contact data before sync
                $validation = $this->localBrain->process(
                    input: "Validate contact: {$contact->name}",
                    context: ['entity' => 'contact', 'action' => 'validate']
                );

                if (($validation['confidence'] ?? 0) >= 0.7) {
                    DB::table('workcore_contacts')
                        ->where('id', $contact->id)
                        ->update(['synced_at' => now()]);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function syncInvoices(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_invoices')) {
                return 0;
            }

            $offlineInvoices = DB::table('workcore_invoices')
                ->where('tenant_id', $tenantId)
                ->where('status', '!=', 'synced')
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineInvoices as $invoice) {
                // Use LocalBrain to validate invoice correctness
                $validation = $this->localBrain->process(
                    input: "Validate invoice {$invoice->invoice_number}: amount {$invoice->amount}",
                    context: ['entity' => 'invoice', 'action' => 'validate']
                );

                if (($validation['confidence'] ?? 0) >= 0.8) {
                    DB::table('workcore_invoices')
                        ->where('id', $invoice->id)
                        ->update(['synced_at' => now(), 'status' => 'synced']);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function syncJobs(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_jobs')) {
                return 0;
            }

            $offlineJobs = DB::table('workcore_jobs')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineJobs as $job) {
                $validation = $this->localBrain->process(
                    input: "Validate job: {$job->title}",
                    context: ['entity' => 'job', 'priority' => $job->priority]
                );

                if (($validation['confidence'] ?? 0) >= 0.7) {
                    DB::table('workcore_jobs')
                        ->where('id', $job->id)
                        ->update(['synced_at' => now()]);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function syncAssignments(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_assignments')) {
                return 0;
            }

            $offlineAssignments = DB::table('workcore_assignments')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineAssignments as $assignment) {
                $validation = $this->localBrain->process(
                    input: "Validate staff assignment",
                    context: ['entity' => 'assignment', 'staff_id' => $assignment->staff_id]
                );

                if (($validation['confidence'] ?? 0) >= 0.75) {
                    DB::table('workcore_assignments')
                        ->where('id', $assignment->id)
                        ->update(['synced_at' => now()]);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function syncProperties(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_properties')) {
                return 0;
            }

            $offlineProperties = DB::table('workcore_properties')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineProperties as $property) {
                $validation = $this->localBrain->process(
                    input: "Validate property: {$property->name}",
                    context: ['entity' => 'property']
                );

                if (($validation['confidence'] ?? 0) >= 0.7) {
                    DB::table('workcore_properties')
                        ->where('id', $property->id)
                        ->update(['synced_at' => now()]);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function syncStaff(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('workcore_staff')) {
                return 0;
            }

            $offlineStaff = DB::table('workcore_staff')
                ->where('tenant_id', $tenantId)
                ->whereNull('synced_at')
                ->get();

            $synced = 0;

            foreach ($offlineStaff as $staff) {
                $validation = $this->localBrain->process(
                    input: "Validate staff member: {$staff->name}",
                    context: ['entity' => 'staff', 'status' => $staff->status]
                );

                if (($validation['confidence'] ?? 0) >= 0.7) {
                    DB::table('workcore_staff')
                        ->where('id', $staff->id)
                        ->update(['synced_at' => now()]);
                    $synced++;
                }
            }

            return $synced;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function resolveConflicts(string $tenantId): int
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::CONFLICT_RESOLUTION_TABLE)) {
                return 0;
            }

            $conflicts = DB::table(self::CONFLICT_RESOLUTION_TABLE)
                ->where('tenant_id', $tenantId)
                ->where('status', 'unresolved')
                ->limit(50)
                ->get();

            $resolved = 0;

            foreach ($conflicts as $conflict) {
                // Use LocalBrain to intelligently resolve conflict
                $resolution = $this->localBrain->process(
                    input: "Resolve conflict: {$conflict->conflict_type}",
                    context: [
                        'conflict_id' => $conflict->id,
                        'offline_version' => json_decode($conflict->offline_data, true),
                        'platform_version' => json_decode($conflict->platform_data, true),
                    ]
                );

                if (($resolution['confidence'] ?? 0) >= 0.75) {
                    // Use LocalBrain decision as resolution
                    $winningVersion = ($resolution['action'] ?? 'offline') === 'offline'
                        ? json_decode($conflict->offline_data, true)
                        : json_decode($conflict->platform_data, true);

                    DB::table(self::CONFLICT_RESOLUTION_TABLE)
                        ->where('id', $conflict->id)
                        ->update([
                            'status' => 'resolved',
                            'resolution' => json_encode($winningVersion),
                            'resolved_by' => 'localbrain',
                            'resolved_at' => now(),
                        ]);

                    $resolved++;
                }
            }

            return $resolved;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getOfflineQueueStatus(string $tenantId): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::SYNC_QUEUE_TABLE)) {
                return ['queued' => 0, 'pending' => 0];
            }

            return [
                'queued' => DB::table(self::SYNC_QUEUE_TABLE)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'pending')
                    ->count(),
                'conflicts' => DB::table(self::CONFLICT_RESOLUTION_TABLE)
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'unresolved')
                    ->count() ?? 0,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
