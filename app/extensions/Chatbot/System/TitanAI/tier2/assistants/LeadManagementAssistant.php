<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class LeadManagementAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'lead-management-assistant'; }
    public static function name(): string { return 'Lead Management Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Score leads based on qualification criteria',
            'Analyze lead quality and conversion potential',
            'Identify high-priority leads for follow-up',
            'Track lead sources and effectiveness',
            'Validate lead data and completeness',
            'Route leads to appropriate sales representatives',
            'Match leads based on territory and expertise',
            'Balance workload across sales team',
            'Support lead distribution rules',
            'Optimize lead assignment efficiency',
            'Coordinate lead follow-up timing and sequence',
            'Schedule automated follow-up actions',
            'Track communication history and engagement',
            'Set reminders and alerts for follow-up',
            'Monitor follow-up effectiveness',
            'Track lead-to-customer conversion rates',
            'Analyze conversion patterns and optimization',
            'Identify conversion barriers and opportunities',
            'Monitor lead pipeline health',
            'Generate conversion forecasting',
            'Generate lead performance dashboards',
            'Analyze lead source ROI and effectiveness',
            'Provide lead intelligence for decision-making',
            'Predict lead conversion likelihood',
            'Optimize lead management processes'
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
            'predictive' => true,
            'automation' => true
        ];
    }
}
