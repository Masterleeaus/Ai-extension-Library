<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class TechnicianPayrollAgent extends AbstractAIWorker
{
    public static function id(): string { return 'technician-payroll-agent'; }
    public static function name(): string { return 'Technician Payroll Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return [
            'Calculate provisional job-based pay',
            'Summarise time travel and overtime inputs',
            'Prepare payroll review records',
    ]; }
    public static function permissions(): array { return [
            'payroll.read',
            'time.tracking',
            'jobs.read',
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
