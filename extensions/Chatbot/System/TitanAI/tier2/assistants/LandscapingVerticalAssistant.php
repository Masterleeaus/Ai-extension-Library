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

final class LandscapingVerticalAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'landscaping-vertical-assistant'; }
    public static function name(): string { return 'LandscapingVerticalAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'operations'; }
    public static function vertical(): string { return 'landscaping'; }

    public static function capabilities(): array
    {
        return [
            'Predict landscaping service demands based on season and climate',
            'Optimize landscaping crew scheduling for project types',
            'Manage landscaping equipment maintenance and seasonal prep',
            'Track landscaping material sourcing (plants, mulch, hardscape)',
            'Predict tree and shrub health issues and maintenance needs',
            'Manage lawn fertilization and pest control scheduling',
            'Coordinate irrigation system service and winterization',
            'Track landscaping design and specialty certifications',
            'Generate landscaping ROI for property improvements',
            'Coordinate emergency tree services and storm cleanup',
            'Predict seasonal landscaping upsell opportunities',
            'Track landscaping material costs and seasonal pricing',
            'Manage environmental compliance (pesticide, water usage)'
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
