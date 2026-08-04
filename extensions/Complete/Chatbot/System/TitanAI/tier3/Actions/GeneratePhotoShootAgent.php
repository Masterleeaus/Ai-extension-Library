<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class GeneratePhotoShootAgent
{
    public const DESCRIPTION = 'Generates one staged cleaning-business photoshoot set.';
    public function id(): string { return 'generate-photo-shoot-agent'; }
    public function authority(): string { return 'workcore-only'; }
    public function requiresHumanApproval(): bool { return true; }
}
