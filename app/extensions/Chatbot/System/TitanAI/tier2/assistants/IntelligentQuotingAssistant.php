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

final class IntelligentQuotingAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-quoting-assistant'; }
    public static function name(): string { return 'IntelligentQuotingAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'optimizer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'finance'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously generate quotes using dynamic pricing',
            'Validate scope against customer requirements',
            'Calculate accurate labor, material, and overhead costs',
            'Apply contract pricing and promotional discounts intelligently',
            'Generate professional quote documents with branding',
            'Coordinate internal approval workflows automatically',
            'Track quote-to-order conversion rates and patterns',
            'Recommend quote revisions based on customer feedback',
            'Manage quote expiration and renewal reminders',
            'Analyze quote pricing effectiveness',
            'Provide multiple pricing options and alternatives',
            'Calculate total cost of ownership for customer',
            'Generate quote analytics and conversion insights'
        ];
    }

    public static function permissions(): array
    {
        return [
            'quotes.read',
            'quotes.create',
            'quotes.write',
            'pricing.read',
            'pricing.write',
            'materials.read',
            'labor.read',
            'customers.read',
            'analytics.quoting',
            'communications.send'
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
            'domain' => 'finance',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
