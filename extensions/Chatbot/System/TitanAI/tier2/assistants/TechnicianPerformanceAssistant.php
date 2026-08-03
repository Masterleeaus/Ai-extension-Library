<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class TechnicianPerformanceAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'technician-performance-assistant'; }
    public static function name(): string { return 'Technician Performance Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Track individual technician performance metrics',
            'Monitor productivity and efficiency rates',
            'Track quality scores and customer satisfaction',
            'Analyze skill utilization and development',
            'Monitor safety and compliance metrics',
            'Identify training needs and opportunities',
            'Recommend skill development programs',
            'Track certification progress and status',
            'Support career progression planning',
            'Coordinate mentorship opportunities',
            'Generate technician scorecards',
            'Provide performance feedback and coaching',
            'Set performance goals and track achievement',
            'Identify improvement opportunities',
            'Track performance trends over time',
            'Analyze capacity and utilization patterns',
            'Identify best performers for mentoring',
            'Support workforce optimization',
            'Plan for peak demand periods',
            'Predict staffing needs',
            'Generate performance analytics dashboards',
            'Compare performance across teams',
            'Identify high-performing technicians',
            'Provide performance improvement recommendations',
            'Track KPIs and benchmarks'
        ];
    }

    public static function permissions(): array
    {
        return [
            'technicians.read',
            'analytics.performance',
            'training.read',
            'training.write',
            'certifications.read',
            'quality.read',
            'safety.read',
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
            'analytics' => true,
            'real_time' => true
        ];
    }
}
