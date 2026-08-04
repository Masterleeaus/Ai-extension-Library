<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Support;

final class SkillOwnershipType
{
    public const USER = 'user';
    public const COMPANY = 'company';
    public const PLATFORM = 'platform';

    /** @return list<string> */
    public static function values(): array
    {
        return [self::USER, self::COMPANY, self::PLATFORM];
    }
}
