<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class GenerateMarketingImageAgent
{
    public const DESCRIPTION = 'Generates one cleaning-business marketing image draft.';
    public function id(): string { return 'generate-marketing-image-agent'; }
    public function authority(): string { return 'workcore-only'; }
    public function requiresHumanApproval(): bool { return true; }
}
