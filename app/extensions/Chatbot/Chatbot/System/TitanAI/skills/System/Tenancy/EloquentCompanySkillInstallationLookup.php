<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tenancy;

use App\Extensions\SystemAIChatSkills\System\Contracts\CompanySkillInstallationLookupInterface;
use App\Extensions\SystemAIChatSkills\System\Models\CompanySkill;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;

class EloquentCompanySkillInstallationLookup implements CompanySkillInstallationLookupInterface
{
    public function for(Skill $skill, CompanyContext $company): CompanySkillInstallationContext
    {
        if ($company->companyId === null || $skill->getKey() === null) {
            return CompanySkillInstallationContext::missing();
        }

        $installation = CompanySkill::query()
            ->where('company_id', $company->companyId)
            ->where('skill_id', $skill->getKey())
            ->first();

        if ($installation === null) {
            return CompanySkillInstallationContext::missing();
        }

        $roles = $installation->allowed_roles;
        $roleAllowed = $company->isManager
            || $roles === null
            || $roles === []
            || ($company->role !== null && in_array($company->role, $roles, true));

        return new CompanySkillInstallationContext(
            installed: true,
            enabled: (bool) $installation->is_enabled,
            roleAllowed: $roleAllowed,
            autoUse: (bool) $installation->auto_use,
        );
    }
}
