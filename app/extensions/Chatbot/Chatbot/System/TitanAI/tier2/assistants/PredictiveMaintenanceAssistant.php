<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class PredictiveMaintenanceAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'predictive-maintenance-assistant'; }
    public static function name(): string { return 'Predictive Maintenance Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Monitor equipment health and performance indicators',
            'Analyze vibration, temperature, and usage data',
            'Identify early warning signs of equipment failure',
            'Predict remaining useful life of equipment',
            'Provide maintenance health scores',
            'Analyze failure patterns and root causes',
            'Predict optimal maintenance timing',
            'Calculate cost-benefit of maintenance timing',
            'Suggest maintenance actions based on predictions',
            'Forecast spare parts requirements',
            'Recommend preventive maintenance schedules',
            'Coordinate with operational calendars',
            'Minimize operational disruption',
            'Schedule during low-demand periods',
            'Optimize resource allocation for maintenance',
            'Integrate with IoT sensors and telematics',
            'Process real-time equipment data streams',
            'Manage alert thresholds and notifications',
            'Maintain historical equipment data',
            'Support multiple equipment types',
            'Track maintenance costs and ROI',
            'Compare reactive vs. predictive maintenance costs',
            'Optimize maintenance frequency and timing',
            'Reduce downtime through smart scheduling',
            'Generate maintenance optimization reports'
        ];
    }

    public static function permissions(): array
    {
        return [
            'equipment.read',
            'equipment.write',
            'maintenance.read',
            'maintenance.write',
            'predictive.read',
            'analytics.equipment',
            'iot.read',
            'schedules.read',
            'costs.read',
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
            'iot_integration' => true,
            'real_time' => true,
            'predictive' => true
        ];
    }
}
