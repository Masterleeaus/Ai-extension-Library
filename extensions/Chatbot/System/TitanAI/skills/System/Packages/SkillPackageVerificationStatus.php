<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

final class SkillPackageVerificationStatus
{
    public const UNVERIFIED = 'unverified';
    public const VERIFIED = 'verified';
    public const MODIFIED = 'modified';
    public const CORRUPTED = 'corrupted';
    public const SOURCE_CHANGED = 'source_changed';

    public const ALL = [
        self::UNVERIFIED,
        self::VERIFIED,
        self::MODIFIED,
        self::CORRUPTED,
        self::SOURCE_CHANGED,
    ];

    private function __construct()
    {
    }
}
