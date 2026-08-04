<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use App\Extensions\Chatbot\System\InteractionEngine\LocalBrain\LocalBrainFacade;
use Illuminate\Support\Facades\DB;

class OfflineAIAgentAdapter
{
    protected LocalBrainFacade $localBrain;
    protected const MAX_STEPS = 50;
    protected const CONFIDENCE_THRESHOLD = 0.7;
    protected const EXECUTION_TABLE = 'aiagent_offline_executions';
    protected const WORKFLOWS_TABLE = 'aiagent_workflows';

    public function __construct(LocalBrainFacade $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    public function executeWorkflowOffline(
        string $workflowId,
        array $initialContext,
        array $skills = []
    ): array {
        $workflow = $this->loadWorkflowDefinition($workflowId);

        if (!$workflow) {
            return [
                'success' => false,
                'error' => "Workflow '{$workflowId}' not found",
                'cloud_used' => false,
            ];
        }

        $state = array_merge($initialContext, ['step' => 0, 'results' => []]);
        $executionTrace = [];
        $stepCount = 0;

        // Execute workflow steps using LocalBrain decisions
        while ($stepCount < self::MAX_STEPS && !isset($state['workflow_complete'])) {
            $stepCount++;

            // Get next action from LocalBrain
            $nextAction = $this->localBrain->decideNextAction(
                workflow: $workflow,
                state: $state,
                availableSkills: $skills
            );

            if (!$nextAction || $nextAction['confidence'] < self::CONFIDENCE_THRESHOLD) {
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => 'halt',
                    'reason' => 'Low confidence decision',
                    'confidence' => $nextAction['confidence'] ?? 0,
                ];
                break;
            }

            try {
                $actionResult = $this->executeAction(
                    $nextAction['action_type'],
                    $nextAction['payload'] ?? [],
                    $state,
                    $skills
                );

                $state['results'][$stepCount] = $actionResult;
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => $nextAction['action_type'],
                    'success' => $actionResult['success'] ?? false,
                    'reasoning' => $nextAction['reasoning'] ?? null,
                ];

                if ($nextAction['action_type'] === 'complete') {
                    $state['workflow_complete'] = true;
                }
            } catch (\Exception $e) {
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => $nextAction['action_type'],
                    'error' => $e->getMessage(),
                ];

                // Attempt error recovery via LocalBrain
                $recovery = $this->localBrain->recoverFromError(
                    workflow: $workflow,
                    step: $stepCount,
                    error: $e->getMessage(),
                    state: $state
                );

                if ($recovery['should_retry']) {
                    continue;
                } else {
                    $state['workflow_complete'] = true;
                    $state['failed'] = true;
                }
            }
        }

        $result = [
            'success' => !($state['failed'] ?? false),
            'workflow_id' => $workflowId,
            'steps_executed' => $stepCount,
            'final_state' => $state,
            'execution_trace' => $executionTrace,
            'cloud_used' => false,
        ];

        // Queue for sync when online
        $this->queueWorkflowForSync($workflowId, $initialContext, $result);

        return $result;
    }

    protected function executeAction(
        string $actionType,
        array $payload,
        array $state,
        array $skills
    ): array {
        return match ($actionType) {
            'skill' => $this->invokeSkill($payload['skill_name'] ?? '', $payload['parameters'] ?? [], $skills),
            'decision' => $this->makeDecision($payload['options'] ?? [], $state),
            'query' => $this->queryOfflineData($payload['entity'] ?? '', $payload['filters'] ?? []),
            'update' => $this->updateOfflineData($payload['entity'] ?? '', $payload['id'] ?? '', $payload['data'] ?? []),
            'branch' => ['success' => true, 'branch' => $payload['branch'] ?? 'default'],
            'complete' => ['success' => true, 'workflow_status' => 'completed'],
            default => ['success' => false, 'error' => "Unknown action type: {$actionType}"],
        };
    }

    protected function invokeSkill(string $skillName, array $parameters, array $skills): array
    {
        if (!in_array($skillName, $skills)) {
            return [
                'success' => false,
                'error' => "Skill '{$skillName}' not available",
            ];
        }

        // Execute skill in offline context
        // Skills are deterministic operations on local data
        try {
            $result = $this->executeLocalSkill($skillName, $parameters);
            return ['success' => true, 'skill' => $skillName, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'skill' => $skillName, 'error' => $e->getMessage()];
        }
    }

    protected function executeLocalSkill(string $skillName, array $parameters): mixed
    {
        // Implement actual skill logic here
        // For now, return mock results
        return match ($skillName) {
            'send_email' => ['sent' => true, 'message_id' => uniqid()],
            'create_task' => ['created' => true, 'task_id' => uniqid()],
            'read_file' => ['content' => 'mock file content'],
            'process_data' => ['processed' => true, 'records' => count($parameters)],
            default => ['status' => 'executed', 'skill' => $skillName],
        };
    }

    protected function makeDecision(array $options, array $state): array
    {
        if (empty($options)) {
            return ['success' => false, 'error' => 'No options provided'];
        }

        // Use LocalBrain to select best option
        $decision = $this->localBrain->selectBestOption(
            options: $options,
            context: $state
        );

        return [
            'success' => true,
            'selected_option' => $decision['selected'] ?? $options[0],
            'reasoning' => $decision['reasoning'] ?? null,
            'confidence' => $decision['confidence'] ?? 0.7,
        ];
    }

    protected function queryOfflineData(string $entity, array $filters): array
    {
        $tableName = $this->mapEntityToTable($entity);

        if (!$tableName || !DB::getSchemaBuilder()->hasTable($tableName)) {
            return [
                'success' => false,
                'error' => "Entity '{$entity}' not found",
                'records' => [],
            ];
        }

        $query = DB::table($tableName);

        foreach ($filters as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return [
            'success' => true,
            'entity' => $entity,
            'records' => $query->get()->toArray(),
        ];
    }

    protected function updateOfflineData(string $entity, string $id, array $data): array
    {
        $tableName = $this->mapEntityToTable($entity);

        if (!$tableName || !DB::getSchemaBuilder()->hasTable($tableName)) {
            return [
                'success' => false,
                'error' => "Entity '{$entity}' not found",
            ];
        }

        DB::table($tableName)
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()]));

        return ['success' => true, 'entity' => $entity, 'updated_id' => $id];
    }

    protected function mapEntityToTable(string $entity): ?string
    {
        return match ($entity) {
            'conversation' => 'chatbot_conversations',
            'contact' => 'workcore_contacts',
            'invoice' => 'workcore_invoices',
            'job' => 'workcore_jobs',
            'assignment' => 'workcore_assignments',
            default => null,
        };
    }

    protected function loadWorkflowDefinition(string $workflowId): ?array
    {
        $workflow = DB::table(self::WORKFLOWS_TABLE)
            ->where('id', $workflowId)
            ->first();

        if (!$workflow) {
            return null;
        }

        return [
            'id' => $workflow->id,
            'name' => $workflow->name,
            'steps' => json_decode($workflow->steps, true),
            'initial_state' => json_decode($workflow->initial_state, true) ?? [],
        ];
    }

    protected function queueWorkflowForSync(
        string $workflowId,
        array $initialContext,
        array $result
    ): void {
        $tenantId = auth()->user()->tenant_id ?? null;
        if (!$tenantId) {
            return;
        }

        DB::table(self::EXECUTION_TABLE)->insert([
            'tenant_id' => $tenantId,
            'workflow_id' => $workflowId,
            'initial_context' => json_encode($initialContext),
            'execution_result' => json_encode($result),
            'status' => $result['success'] ? 'completed' : 'failed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function syncQueuedWorkflows(string $tenantId): array
    {
        $queued = DB::table(self::EXECUTION_TABLE)
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereNull('synced_at')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($queued as $execution) {
            try {
                // Sync to cloud
                DB::table(self::EXECUTION_TABLE)
                    ->where('id', $execution->id)
                    ->update(['synced_at' => now()]);
                $synced++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }
}
