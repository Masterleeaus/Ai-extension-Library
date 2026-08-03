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

final class IntelligentEmergencyResponseAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-emergency-response-assistant'; }
    public static function name(): string { return 'IntelligentEmergencyResponseAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'coordinator'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Detect emergency situations in real-time from multiple data sources',
            'Autonomously escalate emergency dispatch with priority routing',
            'Coordinate emergency response across all teams',
            'Calculate optimal emergency technician assignment',
            'Generate emergency communication to customers and technicians',
            'Track emergency response times and effectiveness',
            'Predict emergency resource needs based on incident type',
            'Coordinate with emergency services when needed',
            'Generate emergency incident reports automatically',
            'Recommend emergency prevention strategies',
            'Track emergency patterns and common triggers',
            'Provide emergency readiness dashboards',
            'Coordinate post-emergency follow-up and recovery'
        ];
    }

    public static function permissions(): array
    {
        return [
            'dispatch.read',
            'dispatch.write',
            'emergency.read',
            'emergency.write',
            'technicians.read',
            'jobs.read',
            'jobs.write',
            'communications.send',
            'incidents.write',
            'analytics.emergency',
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
            'intelligence_level' => 'autonomous',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
