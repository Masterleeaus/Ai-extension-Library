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

final class IntelligentSafetyAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-safety-assistant'; }
    public static function name(): string { return 'IntelligentSafetyAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'coordinator'; }
    public static function intelligenceLevel(): string { return 'predictive'; }
    public static function domain(): string { return 'safety'; }

    public static function capabilities(): array
    {
        return [
            'Predict safety risks based on job characteristics and history',
            'Monitor safety metrics in real-time across all teams',
            'Detect and alert on near-miss incidents immediately',
            'Autonomously investigate incident patterns and root causes',
            'Coordinate compliance tracking across regulatory requirements',
            'Generate audit preparation materials automatically',
            'Track worker certifications and compliance status',
            'Create safety communication and training materials',
            'Recommend safety improvements with ROI analysis',
            'Coordinate safety committee activities and reporting',
            'Monitor hazard identification and mitigation tracking',
            'Generate safety performance dashboards and reports',
            'Coordinate safety training and awareness programs'
        ];
    }

    public static function permissions(): array
    {
        return [
            'incidents.read',
            'incidents.write',
            'incidents.create',
            'compliance.read',
            'compliance.write',
            'evidence.read',
            'safety.read',
            'safety.write',
            'training.read',
            'certifications.read',
            'analytics.safety',
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
            'intelligence_level' => 'predictive',
            'domain' => 'safety',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
