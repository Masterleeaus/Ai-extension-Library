<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class CustomerAnalyticsAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'customer-analytics-assistant'; }
    public static function name(): string { return 'Customer Analytics Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Analyze customer lifetime value (CLV)',
            'Track customer churn prediction and prevention',
            'Identify high-value and high-risk customers',
            'Analyze purchase patterns and preferences',
            'Provide customer behavioral insights',
            'Create dynamic customer segments',
            'Analyze segment profitability and behavior',
            'Identify cross-sell and upsell opportunities',
            'Support targeted marketing strategies',
            'Track segment engagement metrics',
            'Analyze service consumption patterns',
            'Track customer satisfaction trends',
            'Monitor repeat business and referral rates',
            'Analyze service preferences by customer type',
            'Provide service improvement recommendations',
            'Predict customer lifetime value',
            'Predict churn risk and timing',
            'Forecast revenue by customer segment',
            'Recommend retention strategies',
            'Predict response to offers and campaigns',
            'Generate customer analytics dashboards',
            'Provide executive summary reports',
            'Track KPIs and benchmarks',
            'Deliver actionable insights',
            'Support data-driven decision making'
        ];
    }

    public static function permissions(): array
    {
        return [
            'customers.read',
            'analytics.customers',
            'segments.read',
            'predictive.read',
            'reports.read',
            'loyalty.read',
            'reviews.read',
            'financial.read'
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
            'real_time' => true,
            'analytics' => true
        ];
    }
}
