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

final class PredictiveMaintenanceOptimizationAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'predictive-maintenance-optimization-assistant'; }
    public static function name(): string { return 'PredictiveMaintenanceOptimizationAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'predictor'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Monitor equipment health using real-time sensor data',
            'Predict equipment failures before they occur',
            'Calculate optimal maintenance timing based on predictive models',
            'Autonomously generate work orders for preventive maintenance',
            'Analyze failure patterns to identify systemic issues',
            'Forecast spare parts requirements based on predictions',
            'Coordinate maintenance scheduling with operations',
            'Track maintenance cost vs. failure cost trade-offs',
            'Generate predictive maintenance dashboards',
            'Recommend equipment upgrades vs. repairs',
            'Calculate remaining useful life for equipment',
            'Integrate with IoT sensors and telematics systems',
            'Provide maintenance ROI analysis and optimization recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'equipment.read',
            'equipment.write',
            'maintenance.read',
            'maintenance.write',
            'predictive.read',
            'analytics.equipment',
            'iot.read',
            'schedules.read',
            'costs.read',
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
            'assistant_type' => 'predictor',
            'intelligence_level' => 'autonomous',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
