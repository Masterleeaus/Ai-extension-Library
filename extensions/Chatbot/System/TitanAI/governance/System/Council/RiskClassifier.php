<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Council;

use App\Extensions\TitanAIGovernance\System\Enums\RiskLevel;

final class RiskClassifier
{
    public function classify(string $action, array $context = []): RiskLevel
    {
        $configured = $context['risk_level'] ?? null;
        if (is_string($configured) && RiskLevel::tryFrom($configured)) {
            return RiskLevel::from($configured);
        }

        $critical = ['refund', 'payment', 'delete', 'terminate_employee', 'legal_submit', 'medical_action'];
        $red = ['cancel_job', 'change_invoice', 'approve_quote', 'send_contract', 'change_permissions'];
        $amber = ['book_job', 'reschedule_job', 'assign_worker', 'create_invoice', 'create_quote'];

        return match (true) {
            in_array($action, $critical, true) => RiskLevel::Critical,
            in_array($action, $red, true) => RiskLevel::Red,
            in_array($action, $amber, true) => RiskLevel::Amber,
            default => RiskLevel::Green,
        };
    }
}
