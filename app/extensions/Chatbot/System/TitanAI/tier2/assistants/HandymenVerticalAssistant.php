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

final class HandymenVerticalAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'handymen-vertical-assistant'; }
    public static function name(): string { return 'HandymenVerticalAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'operations'; }
    public static function vertical(): string { return 'handymen'; }

    public static function capabilities(): array
    {
        return [
            'Optimize handymen job scheduling for skill diversity',
            'Predict handymen scope expansion opportunities',
            'Track handymen skill certifications and specializations',
            'Manage general handymen materials and tool inventory',
            'Coordinate multi-trade jobs with appropriate specialists',
            'Predict property maintenance needs based on age',
            'Generate handymen service package recommendations',
            'Track handymen productivity across diverse tasks',
            'Manage subcontractor coordination for specialized work',
            'Predict seasonal handymen work variations',
            'Recommend property improvement projects to customers',
            'Coordinate emergency repairs and water damage response',
            'Track handymen customer satisfaction by task type'
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
            'subcontractors.read',
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
