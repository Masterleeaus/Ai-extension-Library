<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Enums;

enum RiskLevel: string
{
    case Green = 'green';
    case Amber = 'amber';
    case Red = 'red';
    case Critical = 'critical';

    public function requiresCouncil(): bool
    {
        return $this !== self::Green;
    }

    public function requiresHumanApproval(): bool
    {
        return $this === self::Critical;
    }

    public function minimumReviewers(): int
    {
        return match ($this) {
            self::Green => 0,
            self::Amber => 1,
            self::Red => 2,
            self::Critical => 2,
        };
    }
}
