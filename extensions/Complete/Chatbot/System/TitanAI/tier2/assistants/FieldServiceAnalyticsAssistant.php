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

final class FieldServiceAnalyticsAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'field-service-analytics-assistant'; }
    public static function name(): string { return 'FieldServiceAnalyticsAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'analyzer'; }
    public static function intelligenceLevel(): string { return 'predictive'; }
    public static function domain(): string { return 'analytics'; }

    public static function capabilities(): array
    {
        return [
            'Generate comprehensive operational dashboards in real-time',
            'Analyze service performance metrics by technician and team',
            'Predict business trends and revenue impacts',
            'Generate profitability analysis by service type and customer',
            'Track KPIs against targets with variance analysis',
            'Identify bottlenecks and optimization opportunities',
            'Provide benchmarking against industry standards',
            'Generate executive summaries with actionable insights',
            'Create custom reports for different stakeholder groups',
            'Analyze customer satisfaction trends and drivers',
            'Track employee productivity and development',
            'Predict seasonal demands and staffing needs',
            'Provide data-driven recommendations for strategic decisions'
        ];
    }

    public static function permissions(): array
    {
        return [
            'reports.read',
            'reports.create',
            'analytics.read',
            'analytics.write',
            'financial.read',
            'operational.read',
            'workforce.read',
            'customers.read',
            'jobs.read',
            'technicians.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'analyzer',
            'intelligence_level' => 'predictive',
            'domain' => 'analytics',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
