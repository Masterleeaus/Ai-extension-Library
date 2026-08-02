<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class EditImageAgent
{
    public const DESCRIPTION = 'Performs one governed image edit operation.';
    public function id(): string { return 'edit-image-agent'; }
    public function authority(): string { return 'workcore-only'; }
    public function requiresHumanApproval(): bool { return true; }
}
