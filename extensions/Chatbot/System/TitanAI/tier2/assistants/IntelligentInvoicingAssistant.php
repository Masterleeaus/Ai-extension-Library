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

final class IntelligentInvoicingAssistant extends AbstractAIWorker
{
    use SelfLearningCapability;
    use AutonomousDecisionMaking;
    use RealTimeAdaptation;
    use PerformanceMonitoring;
    use MultiAgentOrchestration;

    public static function id(): string { return 'intelligent-invoicing-assistant'; }
    public static function name(): string { return 'IntelligentInvoicingAssistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function assistantType(): string { return 'coordinator'; }
    public static function intelligenceLevel(): string { return 'autonomous'; }
    public static function domain(): string { return 'finance'; }

    public static function capabilities(): array
    {
        return [
            'Autonomously generate invoices with validated job completion',
            'Verify chargeable items and materials against job records',
            'Calculate accurate taxes, discounts, and fees',
            'Apply contract pricing and special rates correctly',
            'Support partial and progress invoicing options',
            'Generate professional PDF invoices with branding',
            'Coordinate invoice delivery through preferred channels',
            'Manage invoice disputes and adjustments',
            'Track invoice accuracy and timeliness metrics',
            'Generate batch invoices for recurring customers',
            'Monitor days sales outstanding in real-time',
            'Provide invoice analytics and optimization insights',
            'Automate invoice approval workflows'
        ];
    }

    public static function permissions(): array
    {
        return [
            'invoices.read',
            'invoices.create',
            'invoices.write',
            'jobs.read',
            'pricing.read',
            'contracts.read',
            'materials.read',
            'technicians.read',
            'customers.read',
            'analytics.invoicing',
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
            'assistant_type' => 'coordinator',
            'intelligence_level' => 'autonomous',
            'domain' => 'finance',
            'features' => [
                'real_time' => true, 'predictive' => true, 'adaptive' => true, 'autonomous' => true, 'machine_learning' => true
            ]
        ];
    }
}
