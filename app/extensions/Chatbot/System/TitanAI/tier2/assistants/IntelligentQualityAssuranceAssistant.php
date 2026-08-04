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

final class IntelligentQualityAssuranceAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-quality-assurance-assistant'; }
    public static function name(): string { return 'IntelligentQualityAssuranceAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'quality_controller'; }
    public static function intelligenceLevel(): string { return 'predictive'; }
    public static function domain(): string { return 'quality'; }

    public static function capabilities(): array
    {
        return [
            'Evaluate completion evidence against quality standards autonomously',
            'Implement automated quality checks on job documentation',
            'Predict quality issues before they become customer complaints',
            'Track quality metrics by service type and technician',
            'Identify recurring quality problems and root causes',
            'Schedule follow-up inspections for rework verification',
            'Maintain comprehensive quality records and audit trails',
            'Implement quality scoring and rating systems',
            'Flag quality issues for immediate investigation',
            'Coordinate rework scheduling with minimal disruption',
            'Analyze rework patterns for process improvement',
            'Provide quality improvement recommendations to technicians',
            'Create quality dashboards with real-time visibility'
        ];
    }

    public static function permissions(): array
    {
        return [
            'jobs.read',
            'jobs.write',
            'evidence.read',
            'evidence.write',
            'inspections.read',
            'inspections.create',
            'inspections.update',
            'quality.read',
            'quality.write',
            'analytics.quality',
            'compliance.read',
            'technicians.read',
            'rework.read',
            'rework.write'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'quality_controller',
            'intelligence_level' => 'predictive',
            'domain' => 'quality',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
