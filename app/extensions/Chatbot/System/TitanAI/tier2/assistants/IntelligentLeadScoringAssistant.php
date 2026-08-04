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

final class IntelligentLeadScoringAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-lead-scoring-assistant'; }
    public static function name(): string { return 'IntelligentLeadScoringAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'analyzer'; }
    public static function intelligenceLevel(): string { return 'predictive'; }
    public static function domain(): string { return 'sales'; }

    public static function capabilities(): array
    {
        return [
            'Score leads using AI and historical conversion data',
            'Predict lead conversion probability with high accuracy',
            'Automatically route leads to best-fit sales representatives',
            'Analyze lead sources for ROI optimization',
            'Identify high-value customer profiles',
            'Track lead-to-customer conversion funnels',
            'Recommend optimal follow-up timing for each lead',
            'Detect lead quality issues and recommend improvements',
            'Generate lead intelligence dashboards',
            'Coordinate lead distribution for fairness and efficiency',
            'Monitor follow-up effectiveness and recommend coaching',
            'Predict customer lifetime value at lead stage',
            'Optimize lead nurture sequences based on segment'
        ];
    }

    public static function permissions(): array
    {
        return [
            'leads.read',
            'leads.write',
            'leads.score',
            'leads.route',
            'customers.read',
            'analytics.leads',
            'communications.send',
            'campaigns.read',
            'reports.create',
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
            'assistant_type' => 'analyzer',
            'intelligence_level' => 'predictive',
            'domain' => 'sales',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
