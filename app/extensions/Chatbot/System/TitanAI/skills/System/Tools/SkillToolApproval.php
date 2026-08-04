<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tools;

use InvalidArgumentException;

final class SkillToolApproval
{
    public const NEVER = 'never';
    public const CONDITIONAL = 'conditional';
    public const REQUIRED = 'required';

    public const ALL = [self::NEVER, self::CONDITIONAL, self::REQUIRED];

    public static function normalize(mixed $value): string
    {
        $approval = is_string($value) ? strtolower(trim($value)) : self::NEVER;

        if (! in_array($approval, self::ALL, true)) {
            throw new InvalidArgumentException("Invalid skill tool approval policy [{$approval}].");
        }

        return $approval;
    }
}
