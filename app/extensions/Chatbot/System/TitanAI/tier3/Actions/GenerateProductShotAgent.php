<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class GenerateProductShotAgent
{
    public const DESCRIPTION = 'Generates one product or equipment image.';
    public function id(): string { return 'generate-product-shot-agent'; }
    public function authority(): string { return 'workcore-only'; }
    public function requiresHumanApproval(): bool { return true; }
}
