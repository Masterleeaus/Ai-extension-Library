<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Contracts;

use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanyContext;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanySkillInstallationContext;

interface CompanySkillInstallationLookupInterface
{
    public function for(Skill $skill, CompanyContext $company): CompanySkillInstallationContext;
}
