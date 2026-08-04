<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Tier3\Agents\Actions;

final class ScheduleSocialPostAgent
{
    public const DESCRIPTION = 'Schedules one approved social post.';

    public function id(): string
    {
        return 'schedule-social-post-agent';
    }

    public function authority(): string
    {
        return 'workcore-only';
    }
}
