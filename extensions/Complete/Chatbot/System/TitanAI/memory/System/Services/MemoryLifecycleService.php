<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Services;

use App\Extensions\SystemAIChatMemory\System\Models\SystemAIChatMemory;

final class MemoryLifecycleService
{
    public function expireDue(): int
    {
        return SystemAIChatMemory::query()->whereNotNull('expires_at')->where('expires_at', '<=', now())->delete();
    }

    public function forgetScope(int $companyId, string $scopeType, string|int $scopeId): int
    {
        return SystemAIChatMemory::query()->where('company_id', $companyId)->where('scope_type', $scopeType)->where('scope_id', (string) $scopeId)->delete();
    }

    public function decay(float $factor = 0.98): int
    {
        return SystemAIChatMemory::query()->where('pinned', false)->update(['importance' => \DB::raw('importance * '.max(0.0, min(1.0, $factor)))]);
    }
}
