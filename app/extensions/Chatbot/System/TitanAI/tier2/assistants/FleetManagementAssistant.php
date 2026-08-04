<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class FleetManagementAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'fleet-management-assistant'; }
    public static function name(): string { return 'Fleet Management Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Track vehicle availability and status',
            'Monitor mileage and usage patterns',
            'Schedule routine maintenance and inspections',
            'Manage vehicle assignments to technicians',
            'Track vehicle costs and depreciation',
            'Monitor fuel consumption and efficiency',
            'Optimize fuel purchase timing and locations',
            'Identify fuel cost saving opportunities',
            'Track fleet fuel economy metrics',
            'Support alternative fuel vehicles',
            'Track preventive maintenance schedules',
            'Monitor vehicle health and diagnostic data',
            'Flag maintenance needs proactively',
            'Coordinate vehicle servicing with operations',
            'Maintain maintenance history and costs',
            'Integrate with GPS and telematics systems',
            'Track vehicle location and route compliance',
            'Monitor driving behavior and safety metrics',
            'Enable geofencing and alerts',
            'Access real-time vehicle data',
            'Optimize fleet size and composition',
            'Evaluate vehicle replacement timing',
            'Analyze total cost of ownership',
            'Recommend vehicle acquisitions or retirements',
            'Track fleet utilization efficiency'
        ];
    }

    public static function permissions(): array
    {
        return [
            'vehicles.read',
            'vehicles.write',
            'fleet.read',
            'fleet.write',
            'maintenance.read',
            'maintenance.write',
            'telematics.read',
            'fuel.read',
            'costs.read',
            'analytics.fleet',
            'technicians.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
            'telematics' => true,
            'tracking' => true,
            'real_time' => true
        ];
    }
}
