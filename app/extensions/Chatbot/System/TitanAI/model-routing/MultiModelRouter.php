<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier0\ModelRouting;

final class MultiModelRouter
{
    public const DESCRIPTION = 'Selects model profiles through the WorkCore runtime gateway.';

    public function id(): string
    {
        return 'multi-model-router';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
