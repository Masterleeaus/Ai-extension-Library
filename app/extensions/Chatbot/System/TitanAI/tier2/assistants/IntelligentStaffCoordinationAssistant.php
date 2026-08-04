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

final class IntelligentStaffCoordinationAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-staff-coordination-assistant'; }
    public static function name(): string { return 'IntelligentStaffCoordinationAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'coordinator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'hr'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously check staff availability across dimensions',
            'Predict staffing needs based on demand forecasts',
            'Optimize technician workload distribution fairly',
            'Coordinate substitute selection with skill matching',
            'Track performance and recommend development paths',
            'Manage shift swaps and coverage scheduling',
            'Monitor overtime and prevent burnout',
            'Coordinate team building and morale initiatives',
            'Track retention risk indicators',
            'Generate workforce analytics and insights',
            'Recommend staffing optimization strategies',
            'Coordinate training and skill development programs',
            'Track workforce diversity and inclusion metrics'
        ];
    }

    public static function permissions(): array
    {
        return [
            'workers.read',
            'workers.write',
            'availability.read',
            'availability.write',
            'skills.read',
            'skills.write',
            'assignments.read',
            'assignments.write',
            'training.read',
            'certifications.read',
            'schedule.read',
            'analytics.workforce'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'coordinator',
            'intelligence_level' => 'adaptive',
            'domain' => 'hr',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
