<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class GenerateBlogDraftAgent
{
    public const DESCRIPTION = 'Generates one cleaning-business blog draft.';

    public function id(): string
    {
        return 'generate-blog-draft-agent';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
