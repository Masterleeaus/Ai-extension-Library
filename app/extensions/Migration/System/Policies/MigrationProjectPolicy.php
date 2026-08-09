<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Policies;

use App\Extensions\Migration\System\Authorization\CompanyBoundary;
use App\Extensions\Migration\System\Enums\MigrationPermission;
use App\Extensions\Migration\System\Models\MigrationProject;
use App\Models\User;

final class MigrationProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(MigrationPermission::VIEW->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveCompany($user)
            && $user->can(MigrationPermission::CONFIGURE->value);
    }

    public function view(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::VIEW);
    }

    public function configure(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::CONFIGURE);
    }

    public function update(User $user, MigrationProject $project): bool
    {
        return $this->configure($user, $project);
    }

    public function approve(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::APPROVE);
    }

    public function execute(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::EXECUTE);
    }

    public function resolve(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::RESOLVE);
    }

    public function rollback(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::ROLLBACK);
    }

    public function purge(User $user, MigrationProject $project): bool
    {
        return $this->allowed($user, $project, MigrationPermission::PURGE);
    }

    public function delete(User $user, MigrationProject $project): bool
    {
        return $this->purge($user, $project);
    }

    private function allowed(User $user, MigrationProject $project, MigrationPermission $permission): bool
    {
        return CompanyBoundary::allows(
            $this->companyId($user),
            is_numeric($project->getAttribute('company_id')) ? (int) $project->getAttribute('company_id') : null,
        ) && $user->can($permission->value);
    }

    private function hasActiveCompany(User $user): bool
    {
        return $this->companyId($user) !== null;
    }

    private function companyId(User $user): ?int
    {
        $companyId = $user->getAttribute('active_company_id');

        return is_numeric($companyId) && (int) $companyId > 0 ? (int) $companyId : null;
    }
}
