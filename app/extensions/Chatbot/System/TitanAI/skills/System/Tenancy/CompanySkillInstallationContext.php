<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tenancy;

final readonly class CompanySkillInstallationContext
{
    public function __construct(
        public bool $installed,
        public bool $enabled,
        public bool $roleAllowed,
        public bool $autoUse = false,
    ) {}

    public static function missing(): self
    {
        return new self(false, false, false, false);
    }
}
