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

final class IntelligentInventoryAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-inventory-assistant'; }
    public static function name(): string { return 'IntelligentInventoryAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'autonomous_optimizer'; }
    public static function intelligenceLevel(): string { return 'predictive'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Monitor stock levels in real-time with automatic alerts',
            'Predict demand using AI and historical consumption patterns',
            'Autonomously generate purchase orders at optimal times',
            'Calculate economic order quantities and safety stock levels',
            'Suggest alternative suppliers when items are out of stock',
            'Track equipment utilization and predict replacement needs',
            'Manage multi-location inventory with automatic rebalancing',
            'Analyze supplier performance and recommend optimizations',
            'Flag obsolete or slow-moving inventory for action',
            'Calculate inventory carrying costs and optimization opportunities',
            'Track parts usage per job for accurate billing',
            'Forecast seasonal inventory requirements',
            'Generate inventory optimization reports and recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'inventory.read',
            'inventory.write',
            'inventory.adjust',
            'inventory.forecast',
            'equipment.read',
            'equipment.write',
            'suppliers.read',
            'suppliers.write',
            'purchase_orders.read',
            'purchase_orders.create',
            'analytics.inventory',
            'warehouses.read',
            'technicians.read',
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
            'intelligence_level' => 'predictive',
            'domain' => 'operations',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
