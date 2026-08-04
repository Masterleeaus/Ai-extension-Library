<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Memory;

use InvalidArgumentException;

final class MemoryGovernanceService
{
    public function validateWrite(MemoryPolicy $policy, array $record, string $role, bool $approved = false): array
    {
        if (! in_array($role, $policy->writeRoles, true)) {
            throw new InvalidArgumentException('Role cannot write this memory scope.');
        }
        if ($policy->requiresApproval && ! $approved) {
            throw new InvalidArgumentException('Memory write requires approval.');
        }
        if ($policy->scope === MemoryScope::Reflection) {
            $record['status'] = 'suggested';
            $record['authoritative'] = false;
        }
        $record['scope'] = $policy->scope->value;
        $record['importance'] = $policy->importance;
        $record['expires_at'] = $policy->ttlSeconds ? now()->addSeconds($policy->ttlSeconds) : null;
        return $record;
    }
}
