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

final class IntelligentCustomerSuccessAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-customer-success-assistant'; }
    public static function name(): string { return 'IntelligentCustomerSuccessAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'orchestrator'; }
    public static function intelligenceLevel(): string { return 'adaptive'; }
    public static function domain(): string { return 'customer'; }

    public static function capabilities(): array
    {
        return [
            'Resolve customer inquiries through multiple channels autonomously',
            'Provide real-time service status updates to customers',
            'Predict customer satisfaction based on service parameters',
            'Handle complaints with AI-powered resolution strategies',
            'Coordinate proactive customer retention initiatives',
            'Identify at-risk customers and recommend interventions',
            'Manage customer communication preferences across channels',
            'Track complaint resolution effectiveness',
            'Generate customer satisfaction insights and trends',
            'Coordinate with operations for service recovery',
            'Provide personalized recommendations based on history',
            'Enable self-service support with AI chatbot',
            'Maintain customer communication audit trail'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'customers.write',
            'communications.read',
            'communications.send',
            'jobs.read',
            'complaints.read',
            'complaints.write',
            'retention.read',
            'retention.write',
            'feedback.read',
            'analytics.customers',
            'satisfaction.read',
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
            'assistant_type' => 'orchestrator',
            'intelligence_level' => 'adaptive',
            'domain' => 'customer',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
