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

final class IntelligentTrainingAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-training-assistant'; }
    public static function name(): string { return 'IntelligentTrainingAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'coordinator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'hr'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously coordinate technician onboarding processes',
            'Track training progress and certification status in real-time',
            'Recommend personalized training paths based on skills and goals',
            'Generate training materials tailored to individual learning styles',
            'Predict certification readiness and recommend prep timing',
            'Coordinate mentorship program matching based on skills',
            'Track training ROI through performance improvement measurement',
            'Generate training schedules optimized for operations',
            'Identify skill gaps across team and recommend solutions',
            'Create competency assessments and track progression',
            'Recommend advanced training for high performers',
            'Generate training reports and compliance documentation',
            'Coordinate continuing education and certification renewals'
        ];
    }

    public static function permissions(): array
    {
        return [
            'training.read',
            'training.write',
            'training.schedule',
            'certifications.read',
            'certifications.write',
            'technicians.read',
            'skills.read',
            'skills.write',
            'analytics.training',
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
            'assistant_type' => 'coordinator',
            'intelligence_level' => 'adaptive',
            'domain' => 'hr',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
