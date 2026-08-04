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

final class ElectricalVerticalAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'electrical-vertical-assistant'; }
    public static function name(): string { return 'ElectricalVerticalAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'operations'; }
    public static function vertical(): string { return 'electrical'; }

    public static function capabilities(): array
    {
        return [
            'Optimize electrical job scheduling for technician expertise',
            'Manage electrical license and certification tracking',
            'Track electrical code compliance across jurisdictions',
            'Predict electrical panel and wiring upgrade needs',
            'Manage electrical materials inventory and supplier relationships',
            'Track electrical equipment manufacturer recalls',
            'Coordinate electrical permit and inspection coordination',
            'Manage commercial vs. residential electrical specialization',
            'Predict electrical safety hazards and recommend solutions',
            'Generate electrical service performance metrics',
            'Recommend electrical safety upgrades to customers',
            'Coordinate emergency electrical dispatch',
            'Track electrical efficiency improvements and LED upgrades'
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
            'certifications.read',
            'compliance.read',
            'permits.read',
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
