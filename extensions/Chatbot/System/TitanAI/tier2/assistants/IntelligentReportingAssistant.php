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

final class IntelligentReportingAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-reporting-assistant'; }
    public static function name(): string { return 'IntelligentReportingAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'analyzer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'analytics'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously generate comprehensive operational reports',
            'Create customized reports for different stakeholders',
            'Track business performance against KPIs and targets',
            'Generate real-time operational dashboards',
            'Predict financial performance and cash flow',
            'Analyze profitability by customer, service, and team',
            'Generate executive summaries with key insights',
            'Track operational efficiency improvements',
            'Create automated scheduled reports',
            'Provide trend analysis and forecasting',
            'Generate compliance and regulatory reports',
            'Track competitive benchmarking metrics',
            'Provide data-driven business recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'reports.read',
            'reports.create',
            'analytics.read',
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
            'intelligence_level' => 'autonomous',
            'domain' => 'analytics',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
