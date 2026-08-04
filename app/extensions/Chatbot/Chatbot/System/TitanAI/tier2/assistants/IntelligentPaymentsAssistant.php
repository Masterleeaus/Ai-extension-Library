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

final class IntelligentPaymentsAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-payments-assistant'; }
    public static function name(): string { return 'IntelligentPaymentsAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'optimizer'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'finance'; }

    public static function capabilities(): array
    {
        return [
            'Monitor payment status across all invoices in real-time',
            'Autonomously send reminders at optimal times',
            'Predict customer payment behavior and propensity',
            'Coordinate payment plan options when needed',
            'Escalate overdue accounts through defined workflows',
            'Handle payment confirmations and receipts automatically',
            'Reconcile payments against invoices in real-time',
            'Manage payment disputes and chargebacks',
            'Track collections effectiveness and KPIs',
            'Generate payment performance analytics',
            'Forecast cash flow based on payment patterns',
            'Optimize payment processing for efficiency',
            'Provide collections strategy recommendations'
        ];
    }

    public static function permissions(): array
    {
        return [
            'payments.read',
            'payments.write',
            'payments.create',
            'invoices.read',
            'customers.read',
            'collections.read',
            'collections.write',
            'analytics.payments',
            'communications.send',
            'gateways.read'
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
