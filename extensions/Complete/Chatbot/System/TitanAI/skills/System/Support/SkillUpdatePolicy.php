<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Support;

final class SkillUpdatePolicy
{
    public const PINNED = 'pinned';
    public const FOLLOW_COMPATIBLE = 'follow_compatible';
    public const FOLLOW_LATEST = 'follow_latest';

    public const ALL = [self::PINNED, self::FOLLOW_COMPATIBLE, self::FOLLOW_LATEST];
}
