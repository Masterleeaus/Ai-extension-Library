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

final class IntelligentFleetAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-fleet-assistant'; }
    public static function name(): string { return 'IntelligentFleetAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'optimizer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Monitor vehicle health and predict maintenance needs',
            'Optimize fuel consumption through route and behavior analysis',
            'Autonomously schedule preventive maintenance',
            'Track vehicle utilization and identify optimization opportunities',
            'Predict vehicle replacement timing based on TCO analysis',
            'Monitor driver behavior and safety metrics in real-time',
            'Coordinate vehicle assignments for optimal utilization',
            'Generate fuel efficiency reports and recommendations',
            'Integrate GPS tracking with real-time vehicle location',
            'Analyze telematics data for safety and efficiency insights',
            'Recommend alternative fuel vehicles where beneficial',
            'Track vehicle depreciation and true cost of ownership',
            'Provide fleet optimization dashboards and KPIs'
        ];
    }

    public static function permissions(): array
    {
        return [
            'vehicles.read',
            'vehicles.write',
            'fleet.read',
            'fleet.write',
            'maintenance.read',
            'maintenance.write',
            'telematics.read',
            'fuel.read',
            'costs.read',
            'analytics.fleet',
            'technicians.read',
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
            'assistant_type' => 'optimizer',
            'intelligence_level' => 'autonomous',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
