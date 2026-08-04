<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\Actions;

use App\Services\AI\Contracts\AbstractAIWorker;

final class TechnicianCertificationAgent extends AbstractAIWorker
{
    public static function id(): string { return 'technician-certification-agent'; }
    public static function name(): string { return 'Technician Certification Agent'; }
    public static function tier(): int { return 3; }
    public static function role(): string { return 'agent'; }
    public static function capabilities(): array { return [
            'Track certification status and expiry',
            'Validate job certification requirements',
            'Recommend training for skill gaps',
    ]; }
    public static function permissions(): array { return [
            'technicians.read',
            'compliance.read',
            'training.read',
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
