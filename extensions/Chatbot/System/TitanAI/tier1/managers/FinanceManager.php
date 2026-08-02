<?php

declare(strict_types=1);

namespace App\Services\AI\Tier1\Managers;

use App\Services\AI\Contracts\AbstractAIWorker;

final class FinanceManager extends AbstractAIWorker
{
    public static function id(): string { return 'finance-manager'; }
    public static function name(): string { return 'Finance Manager'; }
    public static function tier(): int { return 1; }
    public static function role(): string { return 'manager'; }

    public static function capabilities(): array
    {
        return [
            'Quotes, invoices and payments oversight',
            'Overdue account management',
            'Financial summaries'
        ];
    }

    public static function permissions(): array
    {
        return [
            'quotes.read',
            'invoices.read',
            'payments.read',
            'expenses.read'
        ];
    }

    public static function definition(): array
    {
        return parent::definition() + [
            'system_chat' => 'system-ai-chat',
            'runtime' => 'workcore-native-ai-runtime',
            'delegates_to' => 'tier_2_assistants',
            'operational_authority' => 'workcore_api_only',
        ];
    }
}
