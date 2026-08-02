<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;
use App\Services\AI\Tier2\Traits\{
    SelfLearningCapability,
    AutonomousDecisionMaking,
    RealTimeAdaptation,
    PerformanceMonitoring,
    MultiAgentOrchestration
};

final class PressureWashingVerticalAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'pressure-washing-vertical-assistant'; }
    public static function name(): string { return 'PressureWashingVerticalAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'operations'; }
    public static function vertical(): string { return 'pressure_washing'; }

    public static function capabilities(): array
    {
        return [
            'Predict pressure washing demand based on season and weather',
            'Optimize pressure washing routes for efficiency',
            'Track pressure washing equipment maintenance (pumps, nozzles)',
            'Manage pressure washing chemical inventory (soaps, sealers)',
            'Coordinate soft wash vs. high-pressure service appropriately',
            'Predict surface damage risks and recommend solutions',
            'Track eco-friendly pressure washing compliance',
            'Manage commercial vs. residential pressure washing specialization',
            'Predict seasonal upsell opportunities (sealing, staining)',
            'Track water usage and conservation practices',
            'Generate pressure washing customer satisfaction metrics',
            'Coordinate emergency flood cleanup services',
            'Track pressure washing equipment cost and ROI'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'jobs.write',
            'customers.read',
            'equipment.read',
            'inventory.read',
            'technicians.read',
            'analytics.operations',
            'compliance.read',
            'reports.create'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'orchestrator',
            'intelligence_level' => 'adaptive',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
