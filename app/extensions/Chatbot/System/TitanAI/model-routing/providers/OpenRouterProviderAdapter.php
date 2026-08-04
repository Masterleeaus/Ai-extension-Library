<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier0\ModelRouting\Providers;

final class OpenRouterProviderAdapter
{
    public const DESCRIPTION = 'Adapts OpenRouter to the runtime model provider contract.';

    public function id(): string
    {
        return 'open-router-provider-adapter';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
