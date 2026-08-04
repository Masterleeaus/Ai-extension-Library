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

final class IntelligentRecurringServicesAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-recurring-services-assistant'; }
    public static function name(): string { return 'IntelligentRecurringServicesAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'optimizer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'operations'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously optimize recurring service schedules',
            'Predict renewal and retention patterns',
            'Auto-generate recurring service recommendations',
            'Track contract lifecycle and renewal opportunities',
            'Predict churn risk for recurring customers',
            'Coordinate upsell opportunities on recurring services',
            'Generate recurring revenue forecasts',
            'Optimize recurring service pricing strategies',
            'Track recurring service profitability by customer',
            'Automate renewal confirmation and reminder processes',
            'Predict optimal service frequency adjustments',
            'Recommend service plan changes to customers',
            'Generate recurring revenue analytics and dashboards'
        ];
    }

    public static function permissions(): array
    {
        return [
            'recurrence.read',
            'recurrence.write',
            'recurrence.create',
            'contracts.read',
            'contracts.write',
            'jobs.read',
            'customers.read',
            'analytics.recurring',
            'pricing.read',
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
