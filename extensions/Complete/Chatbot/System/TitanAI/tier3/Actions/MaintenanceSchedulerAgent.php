<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class MaintenanceSchedulerAgent extends AbstractAIWorker
{
    public static function id(): string { return 'maintenance-scheduler-agent'; }
    public static function name(): string { return 'Maintenance Scheduler Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return [
            'Plan preventive maintenance windows',
            'Coordinate technicians parts and equipment',
            'Track warranty and contract constraints',
    ]; }
    public static function permissions(): array { return [
            'maintenance.schedule',
            'equipment.read',
            'contracts.read',
    ]; }
    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_4_tools',
            'operational_authority' => 'workcore_api_only',
            'execution_mode' => 'governed_delegation',
            'requires_confirmation_for_mutation' => true,
        ];
    }
}
