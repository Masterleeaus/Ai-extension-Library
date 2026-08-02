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

final class HVACVerticalAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'h-v-a-c-vertical-assistant'; }
    public static function name(): string { return 'HVACVerticalAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'operations'; }
    public static function vertical(): string { return 'hvac'; }

    public static function capabilities(): array
    {
        return [
            'Predict HVAC system maintenance needs based on age and usage',
            'Optimize HVAC job scheduling for technician expertise',
            'Manage HVAC certification tracking (EPA, manufacturer)',
            'Track HVAC equipment inventory and specialization',
            'Coordinate seasonal HVAC demand (heating vs. cooling)',
            'Manage HVAC parts sourcing and supplier relationships',
            'Track HVAC system manufacturer recalls and bulletins',
            'Predict HVAC replacement needs and recommend upgrades',
            'Manage warranty claims and manufacturer relationships',
            'Generate HVAC service performance metrics',
            'Recommend HVAC maintenance plans to customers',
            'Coordinate emergency HVAC service dispatch',
            'Track HVAC system efficiency improvements'
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
            'reports.create',
            'warranties.read'
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
