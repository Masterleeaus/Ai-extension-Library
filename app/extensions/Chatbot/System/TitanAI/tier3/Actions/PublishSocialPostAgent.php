<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class PublishSocialPostAgent
{
    public const DESCRIPTION = 'Publishes one approved social post.';

    public function id(): string
    {
        return 'publish-social-post-agent';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
