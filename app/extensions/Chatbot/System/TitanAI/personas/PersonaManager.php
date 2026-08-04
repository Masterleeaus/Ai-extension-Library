<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier0\Personas;

final class PersonaManager
{
    public const DESCRIPTION = 'Manages tenant-scoped AI personas and presentation profiles.';

    public function id(): string
    {
        return 'persona-manager';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
