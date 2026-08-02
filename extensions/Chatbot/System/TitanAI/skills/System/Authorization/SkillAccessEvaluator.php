<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Authorization;

use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;

final class SkillAccessEvaluator
{
    public function preview(SkillAccessContext $context): bool
    {
        return $context->isActive
            && ($context->visibility === SkillVisibility::PLATFORM_PUBLIC || $context->isPublic);
    }

    public function view(SkillAccessContext $context): bool
    {
        if ($context->isAdmin || $context->isOwner() || $context->canManageCompanySkill()) {
            return true;
        }

        if (! $context->isActive) {
            return false;
        }

        if ($context->visibility === SkillVisibility::PLATFORM_PUBLIC || $context->isPublic) {
            return true;
        }

        if ($context->isAttached && $this->attachmentEligible($context)) {
            return true;
        }

        if ($context->ownershipType === SkillOwnershipType::COMPANY && $context->isSameCompany()) {
            return $context->visibility === SkillVisibility::COMPANY_SHARED;
        }

        return $context->hasEnabledCompanyInstallation() && $this->installationEligible($context);
    }

    public function add(SkillAccessContext $context): bool
    {
        return $context->isActive
            && ($context->isAdmin
                || $context->isOwner()
                || $context->visibility === SkillVisibility::PLATFORM_PUBLIC
                || $context->isPublic
                || ($context->ownershipType === SkillOwnershipType::COMPANY
                    && $context->isSameCompany()
                    && $context->visibility === SkillVisibility::COMPANY_SHARED));
    }

    public function remove(SkillAccessContext $context): bool
    {
        return $context->isAttached;
    }

    public function activate(SkillAccessContext $context): bool
    {
        return $context->isActive
            && (($context->isAttached && $this->attachmentEligible($context))
                || ($context->hasEnabledCompanyInstallation() && $this->installationEligible($context)));
    }

    public function update(SkillAccessContext $context): bool
    {
        return $context->isAdmin || $context->isOwner() || $context->canManageCompanySkill();
    }

    public function delete(SkillAccessContext $context): bool
    {
        return ! $context->isSeeded
            && ($context->isAdmin || $context->isOwner() || $context->canManageCompanySkill());
    }

    public function export(SkillAccessContext $context): bool
    {
        return $this->view($context);
    }

    public function readResource(SkillAccessContext $context): bool
    {
        return $this->view($context);
    }

    public function publish(SkillAccessContext $context): bool
    {
        return $context->isAdmin || $context->isOwner() || $context->canManageCompanySkill();
    }

    public function share(SkillAccessContext $context): bool
    {
        return $context->isAdmin || $context->isOwner() || $context->canManageCompanySkill();
    }

    public function manageVersions(SkillAccessContext $context): bool
    {
        return $context->isAdmin || $context->isOwner() || $context->canManageCompanySkill();
    }

    public function installForCompany(SkillAccessContext $context): bool
    {
        if (! $context->isActive || $context->actorCompanyId === null || ! $context->isCompanyManager) {
            return false;
        }

        return $context->visibility === SkillVisibility::PLATFORM_PUBLIC
            || $context->isPublic
            || $context->isSameCompany();
    }

    public function removeFromCompany(SkillAccessContext $context): bool
    {
        return $context->actorCompanyId !== null
            && $context->isCompanyManager
            && $context->isCompanyInstalled;
    }

    public function configureCompany(SkillAccessContext $context): bool
    {
        return $this->removeFromCompany($context);
    }

    private function attachmentEligible(SkillAccessContext $context): bool
    {
        return $context->ownershipType !== SkillOwnershipType::COMPANY || $context->isSameCompany();
    }

    private function installationEligible(SkillAccessContext $context): bool
    {
        return $context->visibility === SkillVisibility::PLATFORM_PUBLIC
            || $context->isPublic
            || $context->isSameCompany();
    }
}
