<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Support;

final class SkillVisibility
{
    public const PRIVATE_USER = 'private_user';
    public const COMPANY_PRIVATE = 'company_private';
    public const COMPANY_SHARED = 'company_shared';
    public const PLATFORM_PRIVATE = 'platform_private';
    public const PLATFORM_PUBLIC = 'platform_public';

    /** @return list<string> */
    public static function values(): array
    {
        return [
            self::PRIVATE_USER,
            self::COMPANY_PRIVATE,
            self::COMPANY_SHARED,
            self::PLATFORM_PRIVATE,
            self::PLATFORM_PUBLIC,
        ];
    }
}
