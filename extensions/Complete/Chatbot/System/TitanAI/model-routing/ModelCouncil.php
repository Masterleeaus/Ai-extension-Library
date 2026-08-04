<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier0\ModelRouting;

final class ModelCouncil
{
    public const DESCRIPTION = 'Coordinates governed multi-model deliberation.';

    public function id(): string
    {
        return 'model-council';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
