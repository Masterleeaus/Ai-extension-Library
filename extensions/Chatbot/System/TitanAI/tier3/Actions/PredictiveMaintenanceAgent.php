<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Integration\WorkCore\DTO\WorkerExecutionContext;
use App\Services\AI\Integration\WorkCore\WorkCoreRuntimeClient;
use App\Services\AI\Tier3\Agents\Contracts\ExecutableAgent;
use App\Services\AI\Tier3\Agents\Results\AgentActionResult;

final class PredictiveMaintenanceAgent extends AbstractAIWorker implements ExecutableAgent
{
    public function __construct(private readonly WorkCoreRuntimeClient $runtime) {}
    
    public static function id(): string { return 'predictive-maintenance-agent'; }
    public static function name(): string { return 'Predictive Maintenance Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array
    {
        return [
            'Predict equipment maintenance needs using AI',
            'Schedule preventive maintenance automatically',
            'Reduce equipment downtime through prediction',
            'Analyze usage patterns and failure modes',
            'Generate maintenance recommendations',
            'Track maintenance history and costs',
            'Create work orders for maintenance jobs',
            'Calculate cost-benefit of maintenance'
        ];
    }
    public static function permissions(): array
    {
        return [
            'equipment.read',
            'equipment.update',
            'maintenance.write',
            'analytics.read',
            'orders.create'
        ];
    }
    public static function definition(): array { return parent::definition()+['system_chat'=>'system-ai-chat','runtime'=>'workcore-native-ai-runtime','tool'=>'maintenance.predict','atomic'=>true]; }
    
    public function execute(object $input, WorkerExecutionContext $context): AgentActionResult
    {
        $equipmentId = $input->equipmentId ?? null;
        if (!$equipmentId) throw new \InvalidArgumentException('PredictiveMaintenanceAgent requires equipmentId.');
        
        $result = $this->runtime->executeTool('maintenance.predict', ['equipment_id' => $equipmentId], $context);
        return AgentActionResult::fromTool($result, 'maintenance.predicted', ['CreatePurchaseOrderAgent', 'NotifyCleanerAgent']);
    }
}
