<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Tenancy;

use App\Extensions\SystemAIChatSkills\System\Contracts\CompanyContextResolverInterface;
use App\Models\User;
use Throwable;

class CompanyContextResolver implements CompanyContextResolverInterface
{
    public function resolve(User $user): CompanyContext
    {
        $companyId = $this->companyId($user);

        if ($companyId === null) {
            return CompanyContext::none();
        }

        $role = $this->role($user);
        $managerRoles = array_map(
            static fn (mixed $value): string => strtolower((string) $value),
            (array) config('system-ai-chat-skills.tenancy.manager_roles', ['owner', 'admin', 'manager'])
        );

        $isManager = $user->isAdmin()
            || ($role !== null && in_array(strtolower($role), $managerRoles, true))
            || $this->ownsCompany($user, $companyId);

        return new CompanyContext($companyId, $role, $isManager);
    }

    private function companyId(User $user): ?int
    {
        $columns = (array) config(
            'system-ai-chat-skills.tenancy.user_company_columns',
            ['company_id', 'team_id']
        );

        foreach ($columns as $column) {
            $value = $this->attribute($user, (string) $column);
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        $owned = $this->attribute($user, 'myCreatedTeam');
        $ownedId = is_object($owned) && method_exists($owned, 'getKey')
            ? $owned->getKey()
            : (is_object($owned) ? ($owned->id ?? null) : null);

        return is_numeric($ownedId) && (int) $ownedId > 0 ? (int) $ownedId : null;
    }

    private function role(User $user): ?string
    {
        foreach ((array) config('system-ai-chat-skills.tenancy.user_role_columns', ['company_role', 'team_role']) as $column) {
            $value = $this->attribute($user, (string) $column);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $member = $this->attribute($user, 'teamMember');
        $role = is_object($member) ? ($member->role ?? null) : null;

        return is_string($role) && trim($role) !== '' ? trim($role) : null;
    }

    private function ownsCompany(User $user, int $companyId): bool
    {
        $owned = $this->attribute($user, 'myCreatedTeam');
        $ownedId = is_object($owned) && method_exists($owned, 'getKey')
            ? $owned->getKey()
            : (is_object($owned) ? ($owned->id ?? null) : null);

        return is_numeric($ownedId) && (int) $ownedId === $companyId;
    }

    private function attribute(User $user, string $key): mixed
    {
        try {
            return $user->getAttribute($key);
        } catch (Throwable) {
            return $user->{$key} ?? null;
        }
    }
}
