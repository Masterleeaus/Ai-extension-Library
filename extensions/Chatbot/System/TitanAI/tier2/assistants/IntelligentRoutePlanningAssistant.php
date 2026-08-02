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

final class IntelligentRoutePlanningAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-route-planning-assistant'; }
    public static function name(): string { return 'IntelligentRoutePlanningAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'autonomous_optimizer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Intelligently sequence jobs by location for minimal travel time',
            'Autonomously optimize routes for fuel efficiency and safety',
            'Predict real-time traffic and adjust routes dynamically',
            'Calculate optimal routing using multiple algorithms',
            'Consider vehicle constraints and driver preferences',
            'Estimate travel time with 95% accuracy using historical data',
            'Support multi-day route planning with consistency',
            'Handle dynamic re-routing for real-time changes',
            'Analyze weather impact on routing',
            'Coordinate multi-vehicle routing optimization',
            'Generate and publish optimized routes to technician devices',
            'Provide turn-by-turn navigation integration',
            'Track actual vs. planned routes for continuous improvement'
        ];
    }

    public static function permissions(): array
    {
        return [
            'routes.read',
            'routes.write',
            'routes.optimize',
            'jobs.read',
            'workers.read',
            'maps.read',
            'traffic.read',
            'analytics.routes',
            'technicians.read',
            'vehicles.read',
            'telematics.read',
            'predictive.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'autonomous_optimizer',
            'intelligence_level' => 'autonomous',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
