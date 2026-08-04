<?php

namespace App\Extensions\Chatbot\System\Services\Offline;

use TitanZero\Interaction\LocalIntelligence\LocalBrain;
use Illuminate\Support\Facades\DB;

class OfflineAIAgentAdapter
{
    protected LocalBrain $localBrain;
    protected const MAX_STEPS = 50;
    protected const CONFIDENCE_THRESHOLD = 0.7;
    protected const EXECUTION_TABLE = 'aiagent_offline_executions';
    protected const WORKFLOWS_TABLE = 'aiagent_workflows';

    public function __construct(LocalBrain $localBrain)
    {
        $this->localBrain = $localBrain;
    }

    public function executeWorkflowOffline(
        string $workflowId,
        array $initialContext,
        array $skills = []
    ): array {
        try {
            $workflow = $this->loadWorkflowDefinition($workflowId);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Workflow not found: {$workflowId}",
                'cloud_used' => false,
            ];
        }

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

        while ($stepCount < self::MAX_STEPS && !isset($state['workflow_complete'])) {
            $stepCount++;

            // Use LocalBrain to determine next step
            $processResult = $this->localBrain->process(
                input: "Workflow step $stepCount in $workflowId",
                context: array_merge($state, ['workflow_id' => $workflowId])
            );

            $confidence = $processResult['confidence'] ?? 0.0;

            if ($confidence < self::CONFIDENCE_THRESHOLD) {
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => 'halt',
                    'reason' => 'Low confidence',
                    'confidence' => $confidence,
                ];
                break;
            }

            $actionType = $this->determineAction($workflow, $stepCount, $state);

            try {
                $actionResult = $this->executeAction(
                    $actionType,
                    $workflow['steps'][$stepCount - 1] ?? [],
                    $state,
                    $skills
                );

                $state['results'][$stepCount] = $actionResult;
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => $actionType,
                    'success' => $actionResult['success'] ?? false,
                ];

                if ($actionType === 'complete') {
                    $state['workflow_complete'] = true;
                }
            } catch (\Exception $e) {
                $executionTrace[] = [
                    'step' => $stepCount,
                    'action' => 'error',
                    'error' => $e->getMessage(),
                ];

                $state['workflow_complete'] = true;
                $state['failed'] = true;
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

        $this->queueWorkflowForSync($workflowId, $initialContext, $result);

        return $result;
    }

    protected function determineAction(array $workflow, int $stepCount, array $state): string
    {
        $steps = $workflow['steps'] ?? [];
        if (isset($steps[$stepCount - 1])) {
            return $steps[$stepCount - 1]['type'] ?? 'complete';
        }
        return 'complete';
    }

    protected function executeAction(
        string $actionType,
        array $stepConfig,
        array $state,
        array $skills
    ): array {
        return match ($actionType) {
            'skill' => $this->invokeSkill($stepConfig['skill_name'] ?? '', $stepConfig['parameters'] ?? [], $skills),
            'decision' => $this->makeDecision($stepConfig['options'] ?? [], $state),
            'query' => $this->queryOfflineData($stepConfig['entity'] ?? '', $stepConfig['filters'] ?? []),
            'update' => $this->updateOfflineData($stepConfig['entity'] ?? '', $stepConfig['id'] ?? '', $stepConfig['data'] ?? []),
            'branch' => ['success' => true, 'branch' => $stepConfig['branch'] ?? 'default'],
            'complete' => ['success' => true, 'workflow_status' => 'completed'],
            default => ['success' => false, 'error' => "Unknown action: {$actionType}"],
        };
    }

    protected function invokeSkill(string $skillName, array $parameters, array $skills): array
    {
        if (!in_array($skillName, $skills)) {
            return ['success' => false, 'error' => "Skill '{$skillName}' not available"];
        }

        try {
            $result = $this->executeLocalSkill($skillName, $parameters);
            return ['success' => true, 'skill' => $skillName, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'skill' => $skillName, 'error' => $e->getMessage()];
        }
    }

    protected function executeLocalSkill(string $skillName, array $parameters): mixed
    {
        return match ($skillName) {
            'send_email' => ['sent' => true, 'message_id' => uniqid()],
            'create_task' => ['created' => true, 'task_id' => uniqid()],
            'read_file' => ['content' => 'local file content'],
            'process_data' => ['processed' => true, 'records' => count($parameters)],
            default => ['status' => 'executed', 'skill' => $skillName],
        };
    }

    protected function makeDecision(array $options, array $state): array
    {
        if (empty($options)) {
            return ['success' => false, 'error' => 'No options provided'];
        }

        $processResult = $this->localBrain->process(
            input: 'Select best option',
            context: $state
        );

        return [
            'success' => true,
            'selected_option' => $options[0],
            'confidence' => $processResult['confidence'] ?? 0.7,
        ];
    }

    protected function queryOfflineData(string $entity, array $filters): array
    {
        $tableName = $this->mapEntityToTable($entity);

        if (!$tableName) {
            return ['success' => false, 'error' => "Entity not found", 'records' => []];
        }

        try {
            if (!DB::getSchemaBuilder()->hasTable($tableName)) {
                return ['success' => false, 'error' => "Table not found", 'records' => []];
            }

            $query = DB::table($tableName);

            foreach ($filters as $column => $value) {
                if (is_array($value)) {
                    $query->whereIn($column, $value);
                } else {
                    $query->where($column, $value);
                }
            }

            return ['success' => true, 'entity' => $entity, 'records' => $query->get()->toArray()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'records' => []];
        }
    }

    protected function updateOfflineData(string $entity, string $id, array $data): array
    {
        $tableName = $this->mapEntityToTable($entity);

        if (!$tableName) {
            return ['success' => false, 'error' => "Entity not found"];
        }

        try {
            if (!DB::getSchemaBuilder()->hasTable($tableName)) {
                return ['success' => false, 'error' => "Table not found"];
            }

            DB::table($tableName)
                ->where('id', $id)
                ->update(array_merge($data, ['updated_at' => now()]));

            return ['success' => true, 'entity' => $entity, 'updated_id' => $id];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
        try {
            if (!DB::getSchemaBuilder()->hasTable(self::WORKFLOWS_TABLE)) {
                return null;
            }

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
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function queueWorkflowForSync(
        string $workflowId,
        array $initialContext,
        array $result
    ): void {
        $tenantId = auth()->check() ? auth()->user()->tenant_id : null;
        if (!$tenantId) {
            return;
        }

        try {
            if (!DB::getSchemaBuilder()->hasTable(self::EXECUTION_TABLE)) {
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
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
