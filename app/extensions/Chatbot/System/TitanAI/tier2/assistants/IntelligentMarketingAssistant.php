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

final class IntelligentMarketingAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-marketing-assistant'; }
    public static function name(): string { return 'IntelligentMarketingAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'sales'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously design and execute multi-channel campaigns',
            'Create dynamic customer segments based on AI analysis',
            'Generate automated email and SMS nurture sequences',
            'Coordinate seasonal promotions and special offers',
            'Manage referral programs with automatic incentive tracking',
            'Coordinate review requests with optimal AI-determined timing',
            'Monitor review platforms and flag negative feedback',
            'Generate review response templates using AI',
            'Draft marketing content (emails, social posts, blogs)',
            'Create SEO-optimized service descriptions',
            'Design re-engagement campaigns for inactive customers',
            'Track campaign performance and ROI in real-time',
            'Provide marketing optimization recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'campaigns.read',
            'campaigns.write',
            'campaigns.execute',
            'segments.read',
            'segments.write',
            'reviews.read',
            'reviews.write',
            'communications.send',
            'content.create',
            'analytics.marketing',
            'loyalty.read',
            'customers.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'assistant_type' => 'orchestrator',
            'intelligence_level' => 'autonomous',
            'domain' => 'sales',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
