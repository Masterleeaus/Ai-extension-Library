<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class PublishBlogPostAgent
{
    public const DESCRIPTION = 'Publishes one approved blog post.';

    public function id(): string
    {
        return 'publish-blog-post-agent';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
