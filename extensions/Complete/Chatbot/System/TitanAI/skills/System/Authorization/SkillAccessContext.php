<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Authorization;

use App\Extensions\SystemAIChatSkills\System\Support\SkillOwnershipType;
use App\Extensions\SystemAIChatSkills\System\Support\SkillVisibility;

final readonly class SkillAccessContext
{
    public function __construct(
        public bool $isAdmin,
        public ?int $actorId,
        public ?int $ownerId,
        public bool $isPublic,
        public bool $isActive,
        public bool $isAttached,
        public bool $isSeeded,
        public ?int $actorCompanyId = null,
        public ?int $skillCompanyId = null,
        public string $ownershipType = SkillOwnershipType::USER,
        public string $visibility = SkillVisibility::PRIVATE_USER,
        public bool $isCompanyManager = false,
        public bool $isCompanyInstalled = false,
        public bool $isCompanyInstallationEnabled = false,
        public bool $isCompanyRoleAllowed = false,
    ) {}

    public function isOwner(): bool
    {
        return $this->ownershipType === SkillOwnershipType::USER
            && $this->actorId !== null
            && $this->ownerId !== null
            && $this->actorId === $this->ownerId;
    }

    public function isSameCompany(): bool
    {
        return $this->actorCompanyId !== null
            && $this->skillCompanyId !== null
            && $this->actorCompanyId === $this->skillCompanyId;
    }

    public function canManageCompanySkill(): bool
    {
        return $this->ownershipType === SkillOwnershipType::COMPANY
            && $this->isSameCompany()
            && $this->isCompanyManager;
    }

    public function hasEnabledCompanyInstallation(): bool
    {
        return $this->actorCompanyId !== null
            && $this->isCompanyInstalled
            && $this->isCompanyInstallationEnabled
            && $this->isCompanyRoleAllowed;
    }
}
