<?php

declare(strict_types=1);

namespace App\Services\AI\Tier2\Assistants;

use App\Services\AI\Contracts\AbstractAIWorker;

final class PaymentsAssistant extends AbstractAIWorker
{
    public static function id(): string { return 'payments-assistant'; }
    public static function name(): string { return 'Payments Assistant'; }
    public static function tier(): int { return 2; }
    public static function role(): string { return 'assistant'; }

    public static function capabilities(): array
    {
        return [
            'Monitor payment status',
            'Coordinate reminders and payment recording',
            'Escalate overdue accounts'
        ];
    }

    public static function permissions(): array
    {
        return [
            'invoices.read',
            'payments.read',
            'customers.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_3_agents',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
